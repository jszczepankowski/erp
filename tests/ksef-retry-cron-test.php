<?php

declare(strict_types=1);

$source = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');
if ($source === '') {
    throw new RuntimeException('Unable to read cron manager source file.');
}

$requiredFragments = [
    "const KSEF_RETRY_PIPELINE_HOOK = 'erp_omd_ksef_retry_pipeline';",
    "add_action(self::KSEF_RETRY_PIPELINE_HOOK, [__CLASS__, 'run_ksef_retry_pipeline']);",
    "wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'erp_omd_five_minutes', self::KSEF_RETRY_PIPELINE_HOOK);",
    'function run_ksef_retry_pipeline()',
    'process_retry_queue(20)',
];

$assertions = 0;
foreach ($requiredFragments as $fragment) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing cron fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF retry cron test passed.\n";
