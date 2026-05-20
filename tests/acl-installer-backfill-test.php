<?php

declare(strict_types=1);

$installerSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-installer.php');

if ($installerSource === '') {
    throw new RuntimeException('Unable to load installer source.');
}

$fragments = [
    'maybe_backfill_acl_audit_option_to_table()',
    "get_option('erp_omd_acl_audit_backfill_done') === '1'",
    "update_option('erp_omd_acl_audit_backfill_done', '1')",
    'ERP_OMD_Acl_Audit_Repository',
    'ERP_OMD_Acl_Service::OPTION_ACL_AUDIT_LOG',
];

$assertions = 0;
foreach ($fragments as $fragment) {
    $assertions++;
    if (strpos($installerSource, $fragment) === false) {
        throw new RuntimeException('Missing installer backfill fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "ACL installer backfill test passed.\n";
