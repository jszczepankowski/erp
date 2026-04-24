<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public function __construct($id)
        {
            $this->ID = (int) $id;
        }
    }
}

if (! function_exists('get_user_by')) {
    function get_user_by($field, $value)
    {
        if ($field !== 'id') {
            return null;
        }

        $users = (array) ($GLOBALS['erp_omd_test_users'] ?? []);
        if (! isset($users[(int) $value])) {
            return null;
        }

        return new WP_User((int) $value);
    }
}

if (! function_exists('user_can')) {
    function user_can($user, $capability)
    {
        $user_id = is_object($user) && isset($user->ID) ? (int) $user->ID : (int) $user;
        $caps = (array) ($GLOBALS['erp_omd_test_user_caps'][$user_id] ?? []);
        return in_array((string) $capability, $caps, true);
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;
        public function __construct(array $clients = []) { $this->clients = $clients; }
        public function find($id) { return $this->clients[(int) $id] ?? null; }
    }
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository
    {
        private $employees;
        public function __construct(array $employees = []) { $this->employees = $employees; }
        public function find($id) { return $this->employees[(int) $id] ?? null; }
    }
}

if (! class_exists('ERP_OMD_Estimate_Repository')) {
    class ERP_OMD_Estimate_Repository
    {
        private $estimates;
        public function __construct(array $estimates = []) { $this->estimates = $estimates; }
        public function find($id) { return $this->estimates[(int) $id] ?? null; }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        public function create(array $data) { return 501; }
    }
}

if (! class_exists('ERP_OMD_Client_Project_Service')) {
    class ERP_OMD_Client_Project_Service
    {
        public function prepare_project(array $data, array $existing_project = null)
        {
            return $data;
        }

        public function validate_project(array $data, array $existing_project = null)
        {
            $errors = [];
            if ((int) ($data['client_id'] ?? 0) <= 0) {
                $errors[] = 'Projekt musi być przypisany do istniejącego klienta.';
            }
            if (trim((string) ($data['name'] ?? '')) === '') {
                $errors[] = 'Nazwa projektu jest wymagana.';
            }

            return $errors;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-project-request-service.php';

final class ProjectRequestServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $service = new ERP_OMD_Project_Request_Service(
            new ERP_OMD_Client_Repository([
                10 => ['id' => 10, 'name' => 'ACME'],
            ]),
            new ERP_OMD_Employee_Repository([
                1 => ['id' => 1],
                2 => ['id' => 2],
            ]),
            new ERP_OMD_Estimate_Repository([
                100 => ['id' => 100, 'client_id' => 10],
            ]),
            new ERP_OMD_Project_Repository(),
            new ERP_OMD_Client_Project_Service()
        );

        $payload = $service->prepare([
            'requester_user_id' => 20,
            'requester_employee_id' => 1,
            'client_id' => 10,
            'project_name' => ' Nowy projekt ',
            'billing_type' => 'fixed_price',
            'budget' => 12000.50,
            'preferred_manager_id' => 2,
            'estimate_id' => 100,
            'brief' => ' Start ',
        ]);
        $GLOBALS['erp_omd_test_users'] = [20 => true, 21 => true];
        $GLOBALS['erp_omd_test_user_caps'] = [20 => [], 21 => ['erp_omd_front_client']];
        $this->assertSame('Nowy projekt', $payload['project_name'], 'Prepare should trim project name.');
        $this->assertSame('fixed_price', $payload['billing_type'], 'Prepare should keep billing type.');
        $this->assertSame(12000.50, $payload['budget'], 'Prepare should map request budget.');

        $errors = $service->validate($payload);
        $this->assertSame([], $errors, 'Valid project request payload should pass validation.');

        $invalidErrors = $service->validate([
            'requester_user_id' => 0,
            'requester_employee_id' => 99,
            'client_id' => 0,
            'project_name' => '',
            'billing_type' => 'weird',
            'preferred_manager_id' => 999,
            'estimate_id' => 1234,
            'brief' => '',
        ]);
        $this->assertTrue(count($invalidErrors) >= 6, 'Invalid payload should report multiple validation errors.');

        $this->assertTrue($service->can_transition_status('new', 'under_review'), 'New request should move to under_review.');
        $this->assertFalse($service->can_transition_status('rejected', 'converted'), 'Rejected request should not convert directly.');

        $conversionErrors = $service->validate_conversion(array_merge($payload, ['status' => 'approved']));
        $this->assertSame([], $conversionErrors, 'Approved request should be convertible when project payload validates.');
        $projectPayload = $service->build_project_payload($payload);
        $this->assertSame(12000.50, (float) ($projectPayload['budget'] ?? 0), 'Conversion payload should keep request budget.');

        $clientRetainerErrors = $service->validate([
            'requester_user_id' => 21,
            'requester_employee_id' => 0,
            'client_id' => 10,
            'project_name' => 'Wniosek klienta',
            'billing_type' => 'retainer',
            'budget' => 0,
            'status' => 'new',
        ]);
        $this->assertTrue(in_array('Typ rozliczenia wniosku jest niepoprawny.', $clientRetainerErrors, true), 'Client request should reject retainer billing type.');

        $clientFixedNoBudgetErrors = $service->validate([
            'requester_user_id' => 21,
            'requester_employee_id' => 0,
            'client_id' => 10,
            'project_name' => 'Wniosek klienta',
            'billing_type' => 'fixed_price',
            'budget' => 0,
            'status' => 'new',
        ]);
        $this->assertTrue(in_array('Dla typu rozliczenia Ryczałt klient musi podać budżet większy od zera.', $clientFixedNoBudgetErrors, true), 'Client fixed-price request should require budget.');

        echo "Assertions: {$this->assertions}\n";
        echo "Project request service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    private function assertTrue($condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertFalse($condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            throw new RuntimeException($message);
        }
    }
}

(new ProjectRequestServiceTestRunner())->run();
