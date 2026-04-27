<?php

class ERP_OMD_KSeF_Export_Service
{
    /** @var mixed */
    private $connector;

    /** @var int */
    private $max_status_polls;

    public function __construct($connector, $max_status_polls = null)
    {
        $this->connector = $connector;
        $this->max_status_polls = is_int($max_status_polls) && $max_status_polls > 0 ? $max_status_polls : 5;
    }

    /**
     * @param string $environment
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|WP_Error
     */
    public function start_export($environment, array $payload)
    {
        return $this->request('POST', '/invoices/exports', [
            'Content-Type' => 'application/json',
        ], $payload, $environment);
    }

    /**
     * @param string $environment
     * @param string $reference_number
     * @return array<string,mixed>|WP_Error
     */
    public function get_export_status($environment, $reference_number)
    {
        return $this->request('GET', '/invoices/exports/' . rawurlencode((string) $reference_number), [], null, $environment);
    }

    /**
     * @param string $environment
     * @param string $reference_number
     * @param int $part_number
     * @return array<string,mixed>|WP_Error
     */
    public function download_export_part($environment, $reference_number, $part_number)
    {
        return $this->request('GET', '/invoices/exports/' . rawurlencode((string) $reference_number) . '/parts/' . max(1, (int) $part_number), [], null, $environment);
    }

    /**
     * @param string $environment
     * @param string $subject_type
     * @param string $from_hwm
     * @param string $to_hwm
     * @return array<string,mixed>
     */
    public function run_incremental_export($environment, $subject_type, $from_hwm, $to_hwm)
    {
        $payload = [
            'subjectType' => (string) $subject_type,
            'from' => (string) $from_hwm,
            'to' => (string) $to_hwm,
            'restrictToPermanentStorageHwmDate' => true,
        ];

        $started = $this->start_export($environment, $payload);
        if ($started instanceof WP_Error) {
            return [
                'ok' => false,
                'status' => 'start_failed',
                'error_code' => (string) $started->get_error_code(),
                'error_message' => (string) $started->get_error_message(),
            ];
        }

        $reference = (string) (($started['json']['referenceNumber'] ?? $started['json']['reference_number'] ?? ''));
        if ($reference === '') {
            return [
                'ok' => false,
                'status' => 'start_invalid_payload',
                'error_code' => 'missing_reference_number',
            ];
        }

        $status_payload = null;
        for ($poll = 0; $poll < $this->max_status_polls; $poll++) {
            $status = $this->get_export_status($environment, $reference);
            if ($status instanceof WP_Error) {
                return [
                    'ok' => false,
                    'status' => 'status_failed',
                    'error_code' => (string) $status->get_error_code(),
                    'error_message' => (string) $status->get_error_message(),
                ];
            }

            $status_payload = (array) ($status['json'] ?? []);
            $phase = strtolower((string) ($status_payload['status'] ?? $status_payload['phase'] ?? ''));
            if (in_array($phase, ['completed', 'ready', 'finished'], true)) {
                break;
            }

            if (in_array($phase, ['failed', 'error'], true)) {
                return [
                    'ok' => false,
                    'status' => 'export_failed',
                    'error_code' => (string) ($status_payload['errorCode'] ?? $status_payload['error_code'] ?? 'export_failed'),
                ];
            }
        }

        $status_payload = is_array($status_payload) ? $status_payload : [];
        $is_truncated = ! empty($status_payload['isTruncated']) || ! empty($status_payload['IsTruncated']);
        $last_permanent = (string) ($status_payload['lastPermanentStorageDate'] ?? $status_payload['LastPermanentStorageDate'] ?? '');
        $hwm = (string) ($status_payload['permanentStorageHwmDate'] ?? $status_payload['PermanentStorageHwmDate'] ?? '');
        $next_hwm = $is_truncated && $last_permanent !== '' ? $last_permanent : $hwm;
        if ($next_hwm === '') {
            $next_hwm = (string) $to_hwm;
        }

        $parts = (array) ($status_payload['parts'] ?? []);

        return [
            'ok' => true,
            'status' => 'completed',
            'reference_number' => $reference,
            'subject_type' => (string) $subject_type,
            'parts' => $parts,
            'is_truncated' => $is_truncated,
            'next_hwm' => $next_hwm,
            'raw_status' => $status_payload,
        ];
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $body
     * @param string $environment
     * @return array<string,mixed>|WP_Error
     */
    private function request($method, $path, array $headers, $body, $environment)
    {
        $headers['X-Environment'] = $this->normalize_environment($environment);
        return $this->connector->request($method, $path, $headers, $body);
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
