<?php

declare(strict_types=1);

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/cost-invoices.php');
$adminRuntime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');

if ($template === '' || $adminRuntime === '') {
    throw new RuntimeException('Failed to read KSeF moderation admin sources.');
}

$assertions = 0;
$fragments = [
    "'ksef-moderation'",
    'Kolejka moderacji KSeF',
    'name="ksef_bulk_action"',
    'Usuń z kolejki',
    'name="retry_keys[]"',
    "case 'bulk_ksef_queue'",
    'function handle_ksef_queue_bulk_action(',
];

foreach ($fragments as $fragment) {
    $assertions++;
    if (strpos($template . "\n" . $adminRuntime, $fragment) === false) {
        throw new RuntimeException('Missing KSeF moderation admin fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF moderation admin page test passed.\n";
