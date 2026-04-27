<?php

class ERP_OMD_KSeF_Incremental_Sync_Service
{
    const LOCK_OPTION_PREFIX = 'erp_omd_ksef_sync_lock_';

    /** @var int */
    private $lock_ttl_seconds;

    /** @var array<int,int> */
    private $retry_schedule_seconds;

    public function __construct($lock_ttl_seconds = null, array $retry_schedule_seconds = null)
    {
        $this->lock_ttl_seconds = is_int($lock_ttl_seconds) && $lock_ttl_seconds > 0 ? $lock_ttl_seconds : 240;
        $this->retry_schedule_seconds = $retry_schedule_seconds ?: [30, 120, 300];
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
        return [
            'ok' => true,
            'environment' => $environment,
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
     * @param string $environment
     * @return string
     */
    private function normalize_environment($environment)
    {
        $env = strtoupper(trim((string) $environment));
        return in_array($env, ['TEST', 'DEMO', 'PRD'], true) ? $env : 'TEST';
    }
}
