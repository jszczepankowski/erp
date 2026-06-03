<?php

declare(strict_types=1);

$autoloader = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-autoloader.php');
$base = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/rest/class-rest-controller.php');
$controller = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/rest/class-rest-hr-controller.php');
$restApi = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');

if ($autoloader === '' || $base === '' || $controller === '' || $restApi === '') {
    throw new RuntimeException('Unable to load REST HR controller source files.');
}

$assertions = 0;
$fragments = [
    [$autoloader, "'ERP_OMD_REST_Controller' => 'includes/rest/class-rest-controller.php'", 'Base REST controller should be autoloaded.'],
    [$autoloader, "'ERP_OMD_REST_HR_Controller' => 'includes/rest/class-rest-hr-controller.php'", 'HR REST controller should be autoloaded.'],
    [$base, 'abstract class ERP_OMD_REST_Controller', 'Base REST controller should exist.'],
    [$base, "const NAMESPACE = 'erp-omd/v1'", 'Base REST controller should centralize namespace.'],
    [$base, 'function register_route(', 'Base REST controller should provide route registration helper.'],
    [$base, 'function readable(', 'Base REST controller should provide readable endpoint helper.'],
    [$base, 'function creatable(', 'Base REST controller should provide creatable endpoint helper.'],
    [$base, 'function editable(', 'Base REST controller should provide editable endpoint helper.'],
    [$base, 'function deletable(', 'Base REST controller should provide deletable endpoint helper.'],
    [$controller, 'class ERP_OMD_REST_HR_Controller extends ERP_OMD_REST_Controller', 'HR REST controller should extend base controller.'],
    [$controller, 'function __construct(ERP_OMD_REST_API $api)', 'HR REST controller should keep callbacks compatible through REST API runtime.'],
    [$controller, "'/employees'", 'HR REST controller should register employees route.'],
    [$controller, "'/employees/(?P<id>\\d+)'", 'HR REST controller should register employee detail route.'],
    [$controller, "'/employees/(?P<id>\\d+)/acl'", 'HR REST controller should register employee ACL route.'],
    [$controller, "'/acl-audit'", 'HR REST controller should register ACL audit route.'],
    [$controller, "'/salary/(?P<id>\\d+)'", 'HR REST controller should register salary detail route.'],
    [$controller, "'/monthly-hours/(?P<year_month>\\d{4}-\\d{2})'", 'HR REST controller should register monthly hours route.'],
    [$restApi, 'new ERP_OMD_REST_HR_Controller($this)', 'REST API should delegate HR route registration to HR controller.'],
    [$restApi, "require_once __DIR__ . '/rest/class-rest-controller.php'", 'REST API should load base controller for direct includes/tests.'],
    [$restApi, "require_once __DIR__ . '/rest/class-rest-hr-controller.php'", 'REST API should load HR controller for direct includes/tests.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "REST HR controller wiring test passed.\n";
