<?php

class ERP_OMD_KSeF_Export_Service
{
    /** @var mixed */
    private $connector;

    /** @var int */
    private $max_status_polls;

    /** @var string */
    private $access_token;

    public function __construct($connector, $max_status_polls = null, $access_token = '')
    {
        $this->connector = $connector;
        $this->max_status_polls = is_int($max_status_polls) && $max_status_polls > 0 ? $max_status_polls : 5;
        $this->access_token = trim((string) $access_token);
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
            'restrictToPermanentStorageHwmDate' => true,
        ];
        $to_hwm = trim((string) $to_hwm);
        if ($to_hwm !== '') {
            $payload['to'] = $to_hwm;
        }

        $started = $this->start_export($environment, $payload);
        if ($started instanceof WP_Error) {
            $error_data = method_exists($started, 'get_error_data') ? (array) $started->get_error_data() : [];
            return [
                'ok' => false,
                'status' => 'start_failed',
                'error_code' => (string) $started->get_error_code(),
                'error_message' => (string) $started->get_error_message(),
                'http_code' => (int) ($error_data['http_code'] ?? 0),
                'retry_after' => (int) ($error_data['retry_after'] ?? 0),
            ];
        }

        $reference = (string) (($started['json']['referenceNumber'] ?? $started['json']['reference_number'] ?? ''));
        if ($reference === '') {
            $started_payload = (array) ($started['json'] ?? []);
            $fallback_error = (string) ($started_payload['code'] ?? $started_payload['errorCode'] ?? $started_payload['error_code'] ?? 'missing_reference_number');
            $fallback_message = (string) ($started_payload['description'] ?? $started_payload['message'] ?? $started_payload['title'] ?? '');
            return [
                'ok' => false,
                'status' => 'start_invalid_payload',
                'error_code' => $fallback_error,
                'error_message' => $fallback_message,
                'raw_start_payload' => $started_payload,
            ];
        }

        $status_payload = null;
        for ($poll = 0; $poll < $this->max_status_polls; $poll++) {
            $status = $this->get_export_status($environment, $reference);
            if ($status instanceof WP_Error) {
                $error_data = method_exists($status, 'get_error_data') ? (array) $status->get_error_data() : [];
                return [
                    'ok' => false,
                    'status' => 'status_failed',
                    'error_code' => (string) $status->get_error_code(),
                    'error_message' => (string) $status->get_error_message(),
                    'http_code' => (int) ($error_data['http_code'] ?? 0),
                    'retry_after' => (int) ($error_data['retry_after'] ?? 0),
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
        if (empty($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }
        if ($this->access_token !== '' && empty($headers['Authorization'])) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        }
        $response = $this->connector->request($method, $path, $headers, $body);
        if ($response instanceof WP_Error) {
            return $response;
        }

        $code = (int) ($response['code'] ?? 0);
        if ($code >= 400) {
            $json = (array) ($response['json'] ?? []);
            $error_code = (string) ($json['code'] ?? $json['errorCode'] ?? $json['error_code'] ?? ('ksef_http_' . $code));
            $error_message = (string) ($json['description'] ?? $json['detail'] ?? $json['message'] ?? $json['title'] ?? ('KSeF export request failed with HTTP ' . $code));
            $headers_map = (array) ($response['headers'] ?? []);
            $retry_after = (int) ($headers_map['retry-after'] ?? $headers_map['Retry-After'] ?? 0);
            return new WP_Error($error_code, $error_message, [
                'http_code' => $code,
                'retry_after' => max(0, $retry_after),
            ]);
        }

        return $response;
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
