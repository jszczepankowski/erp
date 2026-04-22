<?php

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$legacyCount = substr_count($runtime, 'function render_client_dashboard(');
$newCount = substr_count($runtime, 'function render_client_front_dashboard(');
$handleCount = substr_count($runtime, 'function handle_client_screen(');

if ($legacyCount !== 0) {
    throw new RuntimeException('Legacy method render_client_dashboard should not exist.');
}

if ($newCount !== 0) {
    throw new RuntimeException('Method render_client_front_dashboard should not exist.');
}

if ($handleCount !== 1) {
    throw new RuntimeException('Expected exactly one handle_client_screen method.');
}

echo "Assertions: 3\n";
echo "Frontend runtime method naming test passed.\n";
