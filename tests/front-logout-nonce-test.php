<?php

declare(strict_types=1);

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$logoutNeedle = <<<'PHP_NEEDLE'
wp_nonce_url($this->front_url('logout'), 'erp_omd_front_logout')
PHP_NEEDLE;

if (substr_count($runtime, $logoutNeedle) < 3) {
    throw new RuntimeException('All front dashboards should receive a nonce-protected logout URL.');
}

$handleLogoutPosition = strpos($runtime, 'private function handle_logout()');
if ($handleLogoutPosition === false) {
    throw new RuntimeException('handle_logout() method not found.');
}

$handleLogoutBody = substr($runtime, $handleLogoutPosition, 220);
if (strpos($handleLogoutBody, "check_admin_referer('erp_omd_front_logout')") === false) {
    throw new RuntimeException('handle_logout() should verify the logout nonce.');
}

echo "Assertions: 3\n";
echo "Front logout nonce test passed.\n";
