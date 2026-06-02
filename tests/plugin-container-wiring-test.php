<?php

declare(strict_types=1);

$pluginSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-plugin.php');
$containerSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-container.php');
$autoloaderSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-autoloader.php');

if ($pluginSource === '' || $containerSource === '' || $autoloaderSource === '') {
    throw new RuntimeException('Unable to load plugin/container/autoloader source.');
}

if (strpos($autoloaderSource, "'ERP_OMD_Container' => 'includes/class-container.php'") === false) {
    throw new RuntimeException('ERP_OMD_Container must be registered in the autoloader.');
}

if (strpos($autoloaderSource, "'ERP_OMD_HR_Module' => 'includes/class-hr-module.php'") === false) {
    throw new RuntimeException('ERP_OMD_HR_Module must be registered in the autoloader.');
}

if (strpos($pluginSource, 'new ERP_OMD_Role_Repository') !== false || strpos($pluginSource, 'new ERP_OMD_Admin') !== false || strpos($pluginSource, 'new ERP_OMD_REST_API') !== false) {
    throw new RuntimeException('ERP_OMD_Plugin should delegate dependency construction to ERP_OMD_Container.');
}

foreach (['hr_module', 'admin', 'frontend', 'rest_api', 'google_calendar_sync_service'] as $methodName) {
    if (! preg_match('/function\s+' . preg_quote($methodName, '/') . '\s*\(/', $containerSource)) {
        throw new RuntimeException('ERP_OMD_Container is missing method: ' . $methodName);
    }
}

if (strpos($pluginSource, '$this->container->admin()->register_hooks()') === false || strpos($pluginSource, '$this->container->frontend()->register_hooks()') === false || strpos($pluginSource, '$this->container->rest_api()->register_hooks()') === false) {
    throw new RuntimeException('ERP_OMD_Plugin should register hooks through container-managed entry points.');
}

echo "Assertions: 9\n";
echo "Plugin container wiring test passed.\n";
