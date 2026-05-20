<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$aclServiceSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-acl-service.php');

if ($adminSource === '' || $aclServiceSource === '') {
    throw new RuntimeException('Unable to load ACL coverage sources.');
}

if (! preg_match('/ALLOWED_MENU_SLUGS\s*=\s*\[(.*?)\];/s', $aclServiceSource, $allowedMatches)) {
    throw new RuntimeException('Unable to parse ALLOWED_MENU_SLUGS.');
}
preg_match_all('/\'([a-z0-9\-]+)\'/', (string) $allowedMatches[1], $allowedSlugMatches);
$allowedSlugs = array_values(array_unique((array) ($allowedSlugMatches[1] ?? [])));

if (! preg_match('/\$capability_map\s*=\s*\[(.*?)\];/s', $adminSource, $mapMatches)) {
    throw new RuntimeException('Unable to parse admin capability_map.');
}
preg_match_all('/\'([a-z0-9\-]+)\'\s*=>\s*\'([a-z0-9_]+)\'/', (string) $mapMatches[1], $mapPairs);
$mappedSlugs = array_values(array_unique((array) ($mapPairs[1] ?? [])));

$allowedCount = count($allowedSlugs);
$covered = 0;
foreach ($allowedSlugs as $slug) {
    if (in_array($slug, $mappedSlugs, true)) {
        $covered++;
    }
}

$coverage = $allowedCount > 0 ? ($covered / $allowedCount) * 100 : 0.0;
$coverageRounded = round($coverage, 2);

echo 'allowed_slugs=' . $allowedCount . PHP_EOL;
echo 'covered_by_capability_map=' . $covered . PHP_EOL;
echo 'coverage_percent=' . $coverageRounded . PHP_EOL;

if ($allowedCount === 0) {
    throw new RuntimeException('ALLOWED_MENU_SLUGS is empty.');
}
if ($coverage < 80.0) {
    throw new RuntimeException('ACL coverage below 80% threshold.');
}

echo "ACL coverage test passed.\n";
