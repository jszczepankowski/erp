<?php

class ERP_OMD_KSeF_Connector
{
    /** @var string */
    private $base_url;

    /** @var string|null */
    private $resolved_prefix = null;

    /** @var array<int,string> */
    private $prefix_candidates;

    public function __construct($base_url)
    {
        $this->base_url = rtrim((string) $base_url, '/');
        $this->prefix_candidates = ['/v2', '/api/v2', ''];
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string,string> $headers
     * @param array<string,mixed>|string|null $body
     * @param int $timeout
     * @return array<string,mixed>|WP_Error
     */
    public function request($method, $path, array $headers = [], $body = null, $timeout = 25)
    {
        $method = strtoupper((string) $method);
        $normalized_path = '/' . ltrim((string) $path, '/');

        $prefixes = $this->resolved_prefix !== null
            ? [$this->resolved_prefix]
            : $this->prefix_candidates;

        $last_error = null;
        foreach ($prefixes as $prefix) {
            $url = $this->base_url . $prefix . $normalized_path;
            $args = [
                'timeout' => max(5, (int) $timeout),
                'headers' => $headers,
            ];
            if ($method !== 'GET' && $body !== null) {
                $args['body'] = is_string($body) ? $body : wp_json_encode($body);
            }

            $response = $method === 'GET'
                ? wp_remote_get($url, $args)
                : wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $last_error = $response;
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code === 404 || $code === 405) {
                $last_error = new WP_Error('erp_omd_ksef_http_not_found', sprintf('HTTP %1$d for %2$s', $code, $url));
                continue;
            }

            $this->resolved_prefix = $prefix;
            $raw_body = (string) wp_remote_retrieve_body($response);
            $json = json_decode($raw_body, true);

            return [
                'code' => $code,
                'raw_body' => $raw_body,
                'json' => is_array($json) ? $json : null,
                'url' => $url,
            ];
        }

        return $last_error instanceof WP_Error
            ? $last_error
            : new WP_Error('erp_omd_ksef_connector_error', __('Nie udało się połączyć z endpointem KSeF.', 'erp-omd'));
    }
}
