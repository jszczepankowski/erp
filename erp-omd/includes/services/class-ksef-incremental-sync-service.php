<?php

class ERP_OMD_KSeF_Incremental_Sync_Service
{
    const LOCK_OPTION_PREFIX = 'erp_omd_ksef_sync_lock_';
    const MAX_EXPORTS_PER_RUN = 4;

    /** @var int */
    private $lock_ttl_seconds;

    /** @var array<int,int> */
    private $retry_schedule_seconds;

    /** @var mixed|null */
    private $export_service;

    /** @var mixed|null */
    private $import_service;

    /** @var callable */
    private $sleep_callback;

    /** @var int */
    private $max_retry_sleep_seconds;

    public function __construct($lock_ttl_seconds = null, array $retry_schedule_seconds = null, $export_service = null, $import_service = null, $sleep_callback = null, $max_retry_sleep_seconds = null)
    {
        $this->lock_ttl_seconds = is_int($lock_ttl_seconds) && $lock_ttl_seconds > 0 ? $lock_ttl_seconds : 240;
        $this->retry_schedule_seconds = $retry_schedule_seconds ?: [30, 120, 300];
        $this->export_service = $export_service;
        $this->import_service = $import_service;
        $this->sleep_callback = is_callable($sleep_callback)
            ? $sleep_callback
            : static function ($seconds) {
                $seconds = max(0, (int) $seconds);
                if ($seconds > 0) {
                    sleep($seconds);
                }
            };
        $this->max_retry_sleep_seconds = is_int($max_retry_sleep_seconds) && $max_retry_sleep_seconds > 0 ? $max_retry_sleep_seconds : 30;
    }

    /**
     * @param string $environment
     * @param int $max_attempts
     * @return array<string,mixed>
     */
    public function run_scheduled_sync($environment = 'TEST', $max_attempts = 3)
    {
        $environment = $this->normalize_environment($environment);
        $token = $this->acquire_lock($environment);
        if ($token === '') {
            return [
                'ok' => false,
                'status' => 'locked',
                'environment' => $environment,
                'attempts' => 0,
            ];
        }

        $attempt = 0;
        $result = [
            'ok' => false,
            'status' => 'retry_exhausted',
            'environment' => $environment,
            'attempts' => 0,
            'retry_after' => 0,
        ];

        try {
            while ($attempt < max(1, (int) $max_attempts)) {
                $attempt++;
                $sync_result = $this->perform_sync_iteration($environment);
                if ((bool) ($sync_result['ok'] ?? false)) {
                    $result = [
                        'ok' => true,
                        'status' => 'synced',
                        'environment' => $environment,
                        'attempts' => $attempt,
                    ];
                    break;
                }

                if (! $this->is_retryable($sync_result)) {
                    $result = [
                        'ok' => false,
                        'status' => 'failed_non_retryable',
                        'environment' => $environment,
                        'attempts' => $attempt,
                        'error_code' => (string) ($sync_result['error_code'] ?? 'unknown_error'),
                    ];
                    break;
                }

                $retry_after = $this->resolve_retry_after_seconds($sync_result, $attempt);
                $result = [
                    'ok' => false,
                    'status' => 'retrying',
                    'environment' => $environment,
                    'attempts' => $attempt,
                    'retry_after' => $retry_after,
                    'error_code' => (string) ($sync_result['error_code'] ?? 'retryable_error'),
                ];
                if ($attempt < max(1, (int) $max_attempts)) {
                    $this->pause_before_retry($retry_after);
                }
            }

            $this->touch_sync_state($environment, $result);
            return $result;
        } finally {
            $this->release_lock($environment, $token);
        }
    }

    /**
     * @param string $environment
     * @return array<string,mixed>
     */
    protected function perform_sync_iteration($environment)
    {
        if (! is_object($this->export_service) || ! method_exists($this->export_service, 'run_incremental_export')) {
            return [
                'ok' => true,
                'environment' => $environment,
                'status' => 'no_export_service',
            ];
        }

        $subject_types = array_slice($this->resolve_subject_types(), 0, self::MAX_EXPORTS_PER_RUN);
        $include_to_date = (bool) get_option('erp_omd_ksef_sync_include_to_date', false);
        $to_hwm = $include_to_date ? gmdate('Y-m-d\TH:i:s\Z') : '';
        $processed = 0;

        foreach ($subject_types as $subject_type) {
            $from_hwm = $this->get_subject_hwm($environment, $subject_type);
            $export_result = $this->export_service->run_incremental_export($environment, $subject_type, $from_hwm, $to_hwm);
            if (! (bool) ($export_result['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'status' => (string) ($export_result['status'] ?? 'export_failed'),
                    'error_code' => (string) ($export_result['error_code'] ?? 'export_failed'),
                    'retry_after' => (int) ($export_result['retry_after'] ?? 0),
                    'http_code' => (int) ($export_result['http_code'] ?? 0),
                ];
            }

            $next_hwm = (string) ($export_result['next_hwm'] ?? $to_hwm);
            $this->set_subject_hwm($environment, $subject_type, $next_hwm);

            $documents = $this->extract_documents_for_import($export_result, $subject_type);
            if ($documents !== [] && is_object($this->import_service) && method_exists($this->import_service, 'import_documents')) {
                $import_result = (array) $this->import_service->import_documents($documents, 0);
                if ((int) ($import_result['failed'] ?? 0) > 0) {
                    return [
                        'ok' => false,
                        'status' => 'import_failed',
                        'error_code' => 'import_failed',
                        'retry_after' => 0,
                        'http_code' => 0,
                    ];
                }
            }

            $processed++;
        }

        return [
            'ok' => true,
            'environment' => $environment,
            'status' => 'synced',
            'processed_subject_types' => $processed,
                'to_hwm' => $to_hwm,
        ];
    }

    /**
     * @param array<string,mixed> $result
     * @return bool
     */
    protected function is_retryable(array $result)
    {
        $http_code = (int) ($result['http_code'] ?? 0);
        if ($http_code === 429 || $http_code >= 500) {
            return true;
        }

        return ! empty($result['retryable']);
    }

    /**
     * @param array<string,mixed> $result
     * @param int $attempt
     * @return int
     */
    protected function resolve_retry_after_seconds(array $result, $attempt)
    {
        $retry_after = (int) ($result['retry_after'] ?? 0);
        if ($retry_after > 0) {
            return $retry_after;
        }

        $index = max(0, min(count($this->retry_schedule_seconds) - 1, (int) $attempt - 1));
        return (int) ($this->retry_schedule_seconds[$index] ?? 300);
    }

    /**
     * @param int $retry_after
     * @return void
     */
    protected function pause_before_retry($retry_after)
    {
        $seconds = max(0, min((int) $retry_after, $this->max_retry_sleep_seconds));
        if ($seconds <= 0) {
            return;
        }

        call_user_func($this->sleep_callback, $seconds);
    }

    /**
     * @param string $environment
     * @return string
     */
    public function acquire_lock($environment)
    {
        $environment = $this->normalize_environment($environment);
        $key = self::LOCK_OPTION_PREFIX . strtolower($environment);
        $existing = (array) get_option($key, []);
        $now = time();
        $expires_at = (int) ($existing['expires_at'] ?? 0);

        if ($expires_at > $now) {
            return '';
        }

        $token = wp_generate_password(20, false, false);
        update_option($key, [
            'token' => $token,
            'expires_at' => $now + $this->lock_ttl_seconds,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * @param string $environment
     * @param string $token
     * @return void
     */
    public function release_lock($environment, $token)
    {
        $environment = $this->normalize_environment($environment);
        $key = self::LOCK_OPTION_PREFIX . strtolower($environment);
        $existing = (array) get_option($key, []);
        if ((string) ($existing['token'] ?? '') !== (string) $token) {
            return;
        }

        update_option($key, [
            'token' => '',
            'expires_at' => 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param string $environment
     * @param array<string,mixed> $result
     * @return void
     */
    private function touch_sync_state($environment, array $result)
    {
        $environment = $this->normalize_environment($environment);
        $option_key = 'erp_omd_ksef_sync_state_' . strtolower($environment);

        update_option($option_key, [
            'environment' => $environment,
            'status' => (string) ($result['status'] ?? 'unknown'),
            'last_run_at' => gmdate('Y-m-d H:i:s'),
            'attempts' => (int) ($result['attempts'] ?? 0),
            'retry_after' => (int) ($result['retry_after'] ?? 0),
            'error_code' => (string) ($result['error_code'] ?? ''),
        ]);
    }

    /**
     * @param array<string,mixed> $export_result
     * @param string $subject_type
     * @return array<int,array<string,mixed>>
     */
    private function extract_documents_for_import(array $export_result, $subject_type)
    {
        $documents = (array) ($export_result['documents'] ?? $export_result['raw_status']['documents'] ?? []);
        if ($documents === []) {
            return [];
        }

        $mapped = [];
        foreach ($documents as $document) {
            if (! is_array($document)) {
                continue;
            }

            $invoice_number = (string) ($document['invoice_number'] ?? $document['invoiceNumber'] ?? '');
            $reference = (string) ($document['ksef_reference_number'] ?? $document['ksefReferenceNumber'] ?? '');
            if ($invoice_number === '' && $reference === '') {
                continue;
            }

            $mapped[] = [
                'invoice_number' => $invoice_number,
                'issue_date' => (string) ($document['issue_date'] ?? $document['issueDate'] ?? gmdate('Y-m-d')),
                'net_amount' => (float) ($document['net_amount'] ?? $document['netAmount'] ?? 0),
                'vat_amount' => (float) ($document['vat_amount'] ?? $document['vatAmount'] ?? 0),
                'gross_amount' => (float) ($document['gross_amount'] ?? $document['grossAmount'] ?? 0),
                'ksef_reference_number' => $reference,
                'seller_nip' => (string) ($document['seller_nip'] ?? $document['sellerNip'] ?? ''),
                'buyer_nip' => (string) ($document['buyer_nip'] ?? $document['buyerNip'] ?? ''),
                'seller_name' => (string) ($document['seller_name'] ?? $document['sellerName'] ?? ''),
                'items' => (array) ($document['items'] ?? []),
                'document_kind' => (string) ($document['document_kind'] ?? ''),
                'api_sync_source' => 'ksef_sync_hub',
                'subject_type' => (string) $subject_type,
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int,string>
     */
    private function resolve_subject_types()
    {
        $configured = get_option('erp_omd_ksef_sync_subject_types', ['subject1']);
        if (! is_array($configured) || $configured === []) {
            return ['Subject1'];
        }

        $values = [];
        foreach ($configured as $subject_type) {
            $api_subject_type = $this->map_subject_type_for_api($subject_type);
            if ($api_subject_type !== '') {
                $values[] = $api_subject_type;
            }
        }

        return $values === [] ? ['Subject1'] : array_values(array_unique($values));
    }

    /**
     * @param string $environment
     * @param string $subject_type
     * @return string
     */
    private function get_subject_hwm($environment, $subject_type)
    {
        $option_key = 'erp_omd_ksef_sync_hwm_' . strtolower($this->normalize_environment($environment)) . '_' . $this->normalize_subject_type_key($subject_type);
        $hwm = (string) get_option($option_key, '');
        if ($hwm !== '') {
            return $hwm;
        }

        $fallback_hours = max(1, (int) get_option('erp_omd_ksef_sync_backfill_hours', 24));
        return gmdate('Y-m-d\TH:i:s\Z', time() - ($fallback_hours * HOUR_IN_SECONDS));
    }

    /**
     * @param string $environment
     * @param string $subject_type
     * @param string $hwm
     * @return void
     */
    private function set_subject_hwm($environment, $subject_type, $hwm)
    {
        $option_key = 'erp_omd_ksef_sync_hwm_' . strtolower($this->normalize_environment($environment)) . '_' . $this->normalize_subject_type_key($subject_type);
        update_option($option_key, (string) $hwm);
    }

    /**
     * @param string $subject_type
     * @return string
     */
    private function map_subject_type_for_api($subject_type)
    {
        $value = trim((string) $subject_type);
        if ($value === '') {
            return '';
        }

        $normalized = strtolower($value);
        $aliases = [
            'subject1' => 'Subject1',
            'seller' => 'Subject1',
            'podmiot1' => 'Subject1',
            'subject2' => 'Subject2',
            'buyer' => 'Subject2',
            'podmiot2' => 'Subject2',
            'subject3' => 'Subject3',
            'podmiot3' => 'Subject3',
            'subjectauthorized' => 'SubjectAuthorized',
            'authorized' => 'SubjectAuthorized',
            'upowazniony' => 'SubjectAuthorized',
        ];

        return $aliases[$normalized] ?? '';
    }

    /**
     * @param string $subject_type
     * @return string
     */
    private function normalize_subject_type_key($subject_type)
    {
        $mapped = $this->map_subject_type_for_api($subject_type);
        if ($mapped === 'SubjectAuthorized') {
            return 'subjectauthorized';
        }

        if ($mapped !== '') {
            return strtolower($mapped);
        }

        return sanitize_key((string) $subject_type);
    }

    /**
     * @param string $environment
     * @return string
     */
    private function normalize_environment($environment)
    {
        $env = strtoupper(trim((string) $environment));
        return in_array($env, ['TEST', 'DEMO', 'PRD'], true) ? $env : 'TEST';
    }
}
