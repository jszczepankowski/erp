<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('is_email')) {
    function is_email($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;

        public function __construct(array $clients = [])
        {
            $this->clients = $clients;
        }

        public function find($id)
        {
            return $this->clients[(int) $id] ?? null;
        }

        public function nip_exists($nip, $exclude_id = null)
        {
            foreach ($this->clients as $id => $client) {
                if ((string) ($client['nip'] ?? '') === (string) $nip && (int) $id !== (int) $exclude_id) {
                    return true;
                }
            }

            return false;
        }
    }
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository
    {
        private $employees;

        public function __construct(array $employees = [])
        {
            $this->employees = $employees;
        }

        public function find($id)
        {
            return $this->employees[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Role_Repository')) {
    class ERP_OMD_Role_Repository
    {
        private $roles;

        public function __construct(array $roles = [])
        {
            $this->roles = $roles;
        }

        public function find($id)
        {
            return $this->roles[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
    }
}

if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository
    {
        private $counts;

        public function __construct(array $counts = [])
        {
            $this->counts = $counts;
        }

        public function count_for_project_by_statuses($project_id, array $statuses)
        {
            return $this->counts[(int) $project_id] ?? 0;
        }
    }
}

if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service
    {
        private $alerts;

        public function __construct(array $alerts = [])
        {
            $this->alerts = $alerts;
        }

        public function alerts_for_entity($entity_type, $entity_id)
        {
            return $this->alerts[$entity_type . ':' . (int) $entity_id] ?? [];
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-client-project-service.php';

final class ClientProjectServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $service = new ERP_OMD_Client_Project_Service(
            new ERP_OMD_Client_Repository([
                1 => ['id' => 1, 'nip' => '1234567890'],
            ]),
            new ERP_OMD_Employee_Repository([
                5 => ['id' => 5],
            ]),
            new ERP_OMD_Role_Repository([
                8 => ['id' => 8],
            ]),
            new ERP_OMD_Project_Repository(),
            new ERP_OMD_Time_Entry_Repository([
                10 => 2,
            ]),
            new ERP_OMD_Alert_Service([
                'project:10' => [
                    ['severity' => 'error', 'code' => 'project_budget_exceeded'],
                ],
            ])
        );

        $preparedClient = $service->prepare_client([
            'name' => ' ACME ',
            'nip' => '123-456-32-18',
            'phone' => '600 700 800',
            'contact_person_phone' => '48 111 222 333',
            'postal_code' => '00950',
            'country' => 'pl',
        ]);
        $this->assertSame('1234563218', $preparedClient['nip'], 'Client NIP should be normalized to digits only.');
        $this->assertSame('+600700800', $preparedClient['phone'], 'Client phone should be normalized with country-style prefix.');
        $this->assertSame('00-950', $preparedClient['postal_code'], 'Postal code should be normalized to 00-000 format.');
        $this->assertSame('PL', $preparedClient['country'], 'Country should be normalized to ISO alpha-2 code.');

        $clientErrors = $service->validate_client([
            'name' => 'Nowy klient',
            'nip' => '1234',
            'email' => 'bad-email',
            'contact_person_email' => 'bad-email',
            'phone' => 'abc',
            'contact_person_phone' => '123',
            'postal_code' => '123',
            'status' => 'active',
            'account_manager_id' => 999,
        ]);
        $this->assertSame(6, count($clientErrors), 'Client validation should report normalized contact and manager issues.');

        $fixedPriceErrors = $service->validate_project([
            'client_id' => 1,
            'name' => 'Projekt A',
            'billing_type' => 'fixed_price',
            'budget' => 0,
            'retainer_monthly_fee' => 100,
            'status' => 'do_rozpoczecia',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'manager_id' => 5,
            'estimate_id' => 0,
            'brief' => '',
            'alert_margin_threshold' => '',
        ]);
        $this->assertTrue(in_array('Projekt fixed_price wymaga dodatniego budżetu.', $fixedPriceErrors, true), 'Fixed price projects should require a positive budget.');
        $this->assertTrue(in_array('Projekt fixed_price nie może mieć opłaty retainer.', $fixedPriceErrors, true), 'Fixed price projects should reject retainer fees.');

        $retainerErrors = $service->validate_project([
            'client_id' => 1,
            'name' => 'Projekt B',
            'billing_type' => 'retainer',
            'budget' => 100,
            'retainer_monthly_fee' => 0,
            'status' => 'do_rozpoczecia',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'manager_id' => 5,
            'estimate_id' => 0,
            'brief' => '',
            'alert_margin_threshold' => '',
        ]);
        $this->assertTrue(in_array('Projekt retainer wymaga dodatniej opłaty miesięcznej.', $retainerErrors, true), 'Retainer projects should require a monthly fee.');
        $this->assertTrue(in_array('Projekt retainer nie powinien mieć budżetu fixed price — ustaw 0.', $retainerErrors, true), 'Retainer projects should reject fixed-price budget values.');

        $lifecycleErrors = $service->validate_project(
            [
                'client_id' => 1,
                'name' => 'Projekt C',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'do_faktury',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'manager_id' => 5,
                'estimate_id' => 0,
                'brief' => '',
                'alert_margin_threshold' => 12,
            ],
            [
                'id' => 10,
                'client_id' => 1,
                'name' => 'Projekt C',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'w_realizacji',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'manager_id' => 5,
                'estimate_id' => 0,
                'brief' => '',
                'alert_margin_threshold' => null,
            ]
        );
        $this->assertTrue(in_array('Projekt nie może przejść do do_faktury, jeśli ma niezatwierdzone wpisy czasu.', $lifecycleErrors, true), 'Project should not enter do_faktury with pending time entries.');
        $this->assertTrue(in_array('Projekt nie może przejść do do_faktury, jeśli ma aktywne alerty krytyczne.', $lifecycleErrors, true), 'Project should not enter do_faktury with critical alerts.');

        echo "Assertions: {$this->assertions}\n";
        echo "Client project service tests passed.\n";
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
}

(new ClientProjectServiceTestRunner())->run();
