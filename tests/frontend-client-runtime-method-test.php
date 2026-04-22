<?php

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$legacyCount = substr_count($runtime, 'function render_client_dashboard(');
$newCount = substr_count($runtime, 'function render_client_front_dashboard(');
$handleCount = substr_count($runtime, 'function handle_client_screen(');
$processClientCount = substr_count($runtime, 'function process_client_request(');
$createClientNoteCount = substr_count($runtime, 'function create_client_project_note(');
$encodedClientNoticeCount = substr_count($runtime, "rawurlencode(\$message)");

if ($legacyCount !== 0) {
    throw new RuntimeException('Legacy method render_client_dashboard should not exist.');
}

if ($newCount !== 0) {
    throw new RuntimeException('Method render_client_front_dashboard should not exist.');
}

if ($handleCount !== 1) {
    throw new RuntimeException('Expected exactly one handle_client_screen method.');
}

if ($processClientCount !== 1) {
    throw new RuntimeException('Expected exactly one process_client_request method.');
}

if ($createClientNoteCount !== 1) {
    throw new RuntimeException('Expected exactly one create_client_project_note method.');
}

if ($encodedClientNoticeCount !== 0) {
    throw new RuntimeException('Client notice redirect should not rawurlencode message.');
}

echo "Assertions: 6\n";
echo "Frontend runtime method naming test passed.\n";
