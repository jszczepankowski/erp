<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-auth-storage.php';

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $GLOBALS['erp_omd_auth_storage_options'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_auth_storage_options'][$key] = $value;
        return true;
    }
}

$GLOBALS['erp_omd_auth_storage_options'] = [];
$assertions = 0;

$storage = new ERP_OMD_KSeF_Auth_Storage();
$storage->save_tokens('TEST', ['access_token' => 'A1', 'refresh_token' => 'R1']);
$storage->save_tokens('PRD', ['access_token' => 'A2', 'refresh_token' => 'R2']);

$test = $storage->get_tokens('TEST');
$prd = $storage->get_tokens('PRD');

$assertions++;
if (($test['access_token'] ?? '') !== 'A1' || ($test['refresh_token'] ?? '') !== 'R1') {
    throw new RuntimeException('Expected TEST environment tokens to be stored correctly.');
}

$assertions++;
if (($prd['access_token'] ?? '') !== 'A2' || ($prd['refresh_token'] ?? '') !== 'R2') {
    throw new RuntimeException('Expected PRD environment tokens to be stored correctly.');
}

$assertions++;
if (($test['access_token'] ?? '') === ($prd['access_token'] ?? '')) {
    throw new RuntimeException('Expected strict environment separation in storage keys.');
}

$storage->clear_tokens('TEST');
$cleared = $storage->get_tokens('TEST');

$assertions++;
if ($cleared !== []) {
    throw new RuntimeException('Expected TEST tokens to be removed after clear_tokens().');
}

echo "OK ({$assertions} assertions)\n";
