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
$handleClientAttachmentUploadCount = substr_count($runtime, 'function handle_client_project_attachment_upload(');
$collectClientArgsCount = substr_count($runtime, 'function collect_client_dashboard_args(');
$encodedClientNoticeCount = substr_count($runtime, "rawurlencode(\$message)");
$historyMonthCollectionCount = substr_count($runtime, "\$args['history_month'] = \$history_month;");
$monthlyStatusSummaryCount = substr_count($runtime, "\$monthly_history_row['status_summary']");
$clientAttachmentUploadCallCount = substr_count($runtime, "media_handle_upload('attachment_file'");

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

if ($handleClientAttachmentUploadCount !== 1) {
    throw new RuntimeException('Expected exactly one handle_client_project_attachment_upload method.');
}

if ($collectClientArgsCount !== 1) {
    throw new RuntimeException('Expected exactly one collect_client_dashboard_args method.');
}

if ($encodedClientNoticeCount !== 0) {
    throw new RuntimeException('Client notice redirect should not rawurlencode message.');
}

if ($historyMonthCollectionCount !== 1) {
    throw new RuntimeException('collect_client_dashboard_args should include history_month propagation.');
}

if ($monthlyStatusSummaryCount !== 1) {
    throw new RuntimeException('Monthly order history should expose status_summary.');
}

if ($clientAttachmentUploadCallCount !== 1) {
    throw new RuntimeException('Client attachment upload should use media_handle_upload for attachment_file.');
}

echo "Assertions: 11\n";
echo "Frontend runtime method naming test passed.\n";
