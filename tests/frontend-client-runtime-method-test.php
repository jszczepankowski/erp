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
$deleteClientAttachmentCount = substr_count($runtime, 'function delete_client_project_attachment(');
$handleClientAttachmentUploadCount = substr_count($runtime, 'function handle_client_project_attachment_upload(');
$collectClientArgsCount = substr_count($runtime, 'function collect_client_dashboard_args(');
$encodedClientNoticeCount = substr_count($runtime, "rawurlencode(\$message)");
$historyMonthCollectionCount = substr_count($runtime, "\$args['history_month'] = \$history_month;");
$deadlineMonthCollectionCount = substr_count($runtime, "\$args['deadline_month'] = \$deadline_month;");
$monthlyStatusSummaryCount = substr_count($runtime, "\$monthly_history_row['status_summary']");
$clientAttachmentUploadCallCount = substr_count($runtime, "media_handle_upload('attachment_file'");
$clientAttachmentFileSignatureValidationCount = substr_count($runtime, 'wp_check_filetype_and_ext(');
$clientAttachmentZipMimeFallbackCount = substr_count($runtime, 'application/x-zip-compressed');
$clientAttachmentAuditNoteCount = substr_count($runtime, 'Dodano załącznik: %1$s (%2$s).');
$clientAttachmentDeletionAuditNoteCount = substr_count($runtime, 'Usunięto załącznik: %1$s (%2$s).');
$clientAttachmentUploadStagedVariableCount = substr_count($runtime, '$uploaded_attachment_id = 0;');
$deleteClientAttachmentActionCount = substr_count($runtime, "\$action === 'delete_project_attachment'");
$deleteAttachmentMediaCleanupCount = substr_count($runtime, 'wp_delete_attachment($attachment_id, true);');
$deleteAttachmentResultCheckCount = substr_count($runtime, '$delete_result = $attachments_repo->delete($attachment_relation_id);');
$attachmentVersionedLabelCount = substr_count($runtime, "sprintf('%s (v%d)'");
$attachmentVersionLabelNormalizationCount = substr_count($runtime, "preg_replace('/\\s*\\(v\\d+\\)\\s*$/i', '', \$base_attachment_label)");

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

if ($deleteClientAttachmentCount !== 1) {
    throw new RuntimeException('Expected exactly one delete_client_project_attachment method.');
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

if ($deadlineMonthCollectionCount !== 1) {
    throw new RuntimeException('collect_client_dashboard_args should include deadline_month propagation.');
}

if ($monthlyStatusSummaryCount !== 1) {
    throw new RuntimeException('Monthly order history should expose status_summary.');
}

if ($clientAttachmentUploadCallCount !== 1) {
    throw new RuntimeException('Client attachment upload should use media_handle_upload for attachment_file.');
}

if ($clientAttachmentFileSignatureValidationCount < 1) {
    throw new RuntimeException('Client attachment upload should validate file signature with wp_check_filetype_and_ext.');
}

if ($clientAttachmentZipMimeFallbackCount < 1) {
    throw new RuntimeException('Client attachment upload should allow x-zip-compressed MIME fallback.');
}

if ($clientAttachmentAuditNoteCount < 1) {
    throw new RuntimeException('Client attachment upload should append a project note entry for uploaded files.');
}

if ($clientAttachmentUploadStagedVariableCount < 1) {
    throw new RuntimeException('Client upload flow should stage attachment id before persisting notes/relations.');
}

if ($clientAttachmentDeletionAuditNoteCount < 1) {
    throw new RuntimeException('Client attachment deletion should append a project note entry.');
}

if ($deleteClientAttachmentActionCount !== 1) {
    throw new RuntimeException('Client request processor should handle delete_project_attachment action.');
}

if ($deleteAttachmentMediaCleanupCount < 1) {
    throw new RuntimeException('Attachment delete flow should cleanup media files when no links remain.');
}

if ($deleteAttachmentResultCheckCount < 1) {
    throw new RuntimeException('Attachment delete flow should handle failed relation deletion.');
}

if ($attachmentVersionedLabelCount < 1) {
    throw new RuntimeException('Attachment upload flow should produce a versioned label suffix (vN).');
}

if ($attachmentVersionLabelNormalizationCount < 1) {
    throw new RuntimeException('Attachment upload flow should normalize existing (vN) suffixes from labels.');
}

echo "Assertions: 23\n";
echo "Frontend runtime method naming test passed.\n";
