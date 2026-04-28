<?php

interface ERP_OMD_KSeF_Auth_Provider_Interface
{
    /**
     * @param string $environment
     * @return array<string,mixed>|WP_Error
     */
    public function get_challenge($environment);

    /**
     * @param string $environment
     * @param string $ksef_token
     * @param string $context_identifier
     * @return array<string,mixed>|WP_Error
     */
    public function authenticate_with_ksef_token($environment, $ksef_token, $context_identifier);

    /**
     * @param string $environment
     * @param string $reference_number
     * @param string $authentication_token
     * @return array<string,mixed>|WP_Error
     */
    public function get_auth_status($environment, $reference_number, $authentication_token);

    /**
     * @param string $environment
     * @param string $authentication_token
     * @return array<string,mixed>|WP_Error
     */
    public function redeem_token($environment, $authentication_token);

    /**
     * @param string $environment
     * @param string $refresh_token
     * @return array<string,mixed>|WP_Error
     */
    public function refresh_access_token($environment, $refresh_token);

    /**
     * @param string $environment
     * @param string $ksef_token
     * @param string $context_identifier
     * @return array<string,mixed>|WP_Error
     */
    public function ensure_access_token($environment, $ksef_token, $context_identifier);
}
