<?php

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$legacyCount = substr_count($runtime, 'function render_client_dashboard(');
$newCount = substr_count($runtime, 'function render_client_front_dashboard(');

if ($legacyCount !== 0) {
    throw new RuntimeException('Legacy method render_client_dashboard should not exist.');
}

if ($newCount !== 1) {
    throw new RuntimeException('Expected exactly one render_client_front_dashboard method.');
}

echo "Assertions: 2\n";
echo "Frontend runtime method naming test passed.\n";
