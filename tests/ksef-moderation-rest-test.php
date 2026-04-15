<?php

declare(strict_types=1);

$source = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');
if ($source === '') {
    throw new RuntimeException('Unable to read REST API source file.');
}

$requiredFragments = [
    "'/ksef/moderation'",
    "'/ksef/moderation/(?P<retry_key>[A-Za-z0-9:\\-_.]+)'",
    "'/ksef/moderation/bulk'",
    'function list_ksef_moderation_queue(',
    'function moderate_ksef_queue_entry(',
    'function bulk_moderate_ksef_queue_entries(',
];

$assertions = 0;
foreach ($requiredFragments as $fragment) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing KSeF moderation REST fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF moderation REST test passed.\n";
