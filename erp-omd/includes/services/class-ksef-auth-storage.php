<?php

class ERP_OMD_KSeF_Auth_Storage
{
    const OPTION_PREFIX = 'erp_omd_ksef_auth_';

    /**
     * @param string $environment
     * @return array<string,mixed>
     */
    public function get_tokens($environment)
    {
        $key = $this->build_option_key($environment);
        $value = get_option($key, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param string $environment
     * @param array<string,mixed> $tokens
     * @return bool
     */
    public function save_tokens($environment, array $tokens)
    {
        $key = $this->build_option_key($environment);
        $existing = $this->get_tokens($environment);

        $payload = array_merge($existing, $tokens, [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'environment' => $this->normalize_environment($environment),
        ]);

        return (bool) update_option($key, $payload);
    }

    /**
     * @param string $environment
     * @return bool
     */
    public function clear_tokens($environment)
    {
        $key = $this->build_option_key($environment);
        return (bool) update_option($key, []);
    }

    /**
     * @param string $environment
     * @return string
     */
    private function build_option_key($environment)
    {
        return self::OPTION_PREFIX . strtolower($this->normalize_environment($environment));
    }

    /**
     * @param string $environment
     * @return string
     */
    private function normalize_environment($environment)
    {
        $env = strtoupper(trim((string) $environment));

        if (! in_array($env, ['TEST', 'DEMO', 'PRD'], true)) {
            return 'TEST';
        }

        return $env;
    }
}
