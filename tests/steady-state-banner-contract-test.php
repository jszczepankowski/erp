<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin.php');
$reportsTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/reports.php');

if ($adminSource === '' || $reportsTemplateSource === '') {
    throw new RuntimeException('Unable to load source files for steady-state banner contract test.');
}

if (strpos($adminSource, "'history_drift_ratio_percent' =>") === false) {
    throw new RuntimeException('Steady-state banner payload should expose history_drift_ratio_percent.');
}

if (strpos($adminSource, "'history_last_sample_at' =>") === false) {
    throw new RuntimeException('Steady-state banner payload should expose history_last_sample_at.');
}

if (strpos($reportsTemplateSource, "Próbki z dryfem: %1\$d/%2\$d (%3\$s%%)") === false) {
    throw new RuntimeException('Reports template should render drift ratio percentage next to drift sample counter.');
}

if (strpos($reportsTemplateSource, "Ostatnia próbka monitoringu: %s") === false) {
    throw new RuntimeException('Reports template should render latest monitoring sample timestamp.');
}

echo "Assertions: 4\n";
echo "Steady-state banner contract test passed.\n";
