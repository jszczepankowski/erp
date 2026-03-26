<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 777;
    }
}

if (! function_exists('current_time')) {
    function current_time($format)
    {
        if ($format === 'Y-m-d') {
            return '2026-03-20';
        }

        return '2026-03-20 12:00:00';
    }
}

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public $code;
        public $message;
        public $data;

        public function __construct($code, $message, $data = [])
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
    }
}

if (! class_exists('ERP_OMD_Estimate_Repository')) {
    class ERP_OMD_Estimate_Repository
    {
        public $records = [];

        public function __construct(array $records)
        {
            $this->records = $records;
        }

        public function find($id)
        {
            return $this->records[(int) $id] ?? null;
        }

        public function mark_accepted($id, $user_id)
        {
            $this->records[(int) $id]['status'] = 'zaakceptowany';
            $this->records[(int) $id]['accepted_by_user_id'] = (int) $user_id;
            $this->records[(int) $id]['accepted_at'] = '2026-03-20 12:00:00';
        }
    }
}

if (! class_exists('ERP_OMD_Estimate_Item_Repository')) {
    class ERP_OMD_Estimate_Item_Repository
    {
        private $items;

        public function __construct(array $items)
        {
            $this->items = $items;
        }

        public function for_estimate($estimate_id)
        {
            return $this->items[(int) $estimate_id] ?? [];
        }
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;

        public function __construct(array $clients)
        {
            $this->clients = $clients;
        }

        public function find($id)
        {
            return $this->clients[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        public $created = [];
        private $projectsByEstimate = [];

        public function find_by_estimate_id($estimate_id)
        {
            return $this->projectsByEstimate[(int) $estimate_id] ?? null;
        }

        public function create(array $data)
        {
            $id = count($this->created) + 100;
            $data['id'] = $id;
            $this->created[$id] = $data;
            $this->projectsByEstimate[(int) $data['estimate_id']] = $data;

            return $id;
        }

        public function find($id)
        {
            return $this->created[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Cost_Repository')) {
    class ERP_OMD_Project_Cost_Repository
    {
        public $created = [];

        public function create(array $data)
        {
            $this->created[] = $data;

            return count($this->created);
        }
    }
}

$GLOBALS['wpdb'] = new class {
    public $queries = [];

    public function query($sql)
    {
        $this->queries[] = $sql;
        return true;
    }
};

require_once __DIR__ . '/../erp-omd/includes/services/class-estimate-service.php';

final class EstimateServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $projectCostRepository = new ERP_OMD_Project_Cost_Repository();
        $service = new ERP_OMD_Estimate_Service(
            new ERP_OMD_Estimate_Repository([
                1 => ['id' => 1, 'client_id' => 10, 'name' => 'Sprint launch', 'status' => 'do_akceptacji'],
                2 => ['id' => 2, 'client_id' => 10, 'name' => 'Archiwalny', 'status' => 'zaakceptowany'],
            ]),
            new ERP_OMD_Estimate_Item_Repository([
                1 => [
                    ['name' => 'Analiza', 'qty' => 2, 'price' => 100.0, 'cost_internal' => 50.0],
                    ['name' => 'Projekt', 'qty' => 1, 'price' => 200.0, 'cost_internal' => 80.0],
                ],
            ]),
            new ERP_OMD_Client_Repository([
                10 => ['id' => 10, 'name' => 'ACME', 'account_manager_id' => 5],
            ]),
            new ERP_OMD_Project_Repository(),
            $projectCostRepository
        );

        $totals = $service->calculate_totals([
            ['qty' => 2, 'price' => 100.0, 'cost_internal' => 50.0],
            ['qty' => 1, 'price' => 200.0, 'cost_internal' => 80.0],
        ]);
        $this->assertSame(400.0, $totals['net'], 'Estimate net total should sum qty * price.');
        $this->assertSame(92.0, $totals['tax'], 'Estimate tax should apply 23% VAT.');
        $this->assertSame(492.0, $totals['gross'], 'Estimate gross should equal net plus VAT.');
        $this->assertSame(130.0, $totals['internal_cost'], 'Estimate internal cost should sum per-item internal cost values.');

        $acceptResult = $service->accept(1);
        $this->assertSame('zaakceptowany', $acceptResult['estimate']['status'], 'Accepting estimate should lock estimate as zaakceptowany.');
        $this->assertSame('fixed_price', $acceptResult['project']['billing_type'], 'Accepted estimate should create fixed-price project.');
        $this->assertSame(400.0, $acceptResult['project']['budget'], 'Accepted estimate project should use net total as project budget.');
        $this->assertSame(1, $acceptResult['project']['estimate_id'], 'Accepted estimate should bind created project to estimate.');
        $this->assertSame('Sprint launch', $acceptResult['project']['name'], 'Accepted estimate should use estimate name as project name.');
        $this->assertSame(2, count($projectCostRepository->created), 'Accepted estimate should copy each internal estimate cost to project costs.');
        $this->assertSame(50.0, $projectCostRepository->created[0]['amount'], 'First copied project cost should match estimate internal cost.');
        $this->assertSame(80.0, $projectCostRepository->created[1]['amount'], 'Second copied project cost should match estimate internal cost.');

        $estimateValidationErrors = $service->validate_estimate(
            ['client_id' => 0, 'name' => '', 'status' => 'bledny'],
            null
        );
        $this->assertSame(3, count($estimateValidationErrors), 'Estimate validation should require name, valid client and valid status.');

        $acceptedEstimateStatusOnlyErrors = $service->validate_estimate(
            ['client_id' => 10, 'name' => 'Archiwalny', 'status' => 'do_akceptacji'],
            ['id' => 2, 'client_id' => 10, 'name' => 'Archiwalny', 'status' => 'zaakceptowany']
        );
        $this->assertSame([], $acceptedEstimateStatusOnlyErrors, 'Accepted estimate should allow status-only changes.');

        $acceptedEstimateChangedNameErrors = $service->validate_estimate(
            ['client_id' => 10, 'name' => 'Archiwalny 2', 'status' => 'do_akceptacji'],
            ['id' => 2, 'client_id' => 10, 'name' => 'Archiwalny', 'status' => 'zaakceptowany']
        );
        $this->assertSame(1, count($acceptedEstimateChangedNameErrors), 'Accepted estimate should reject changing locked fields like name.');

        $validationErrors = $service->validate_item(
            ['name' => '', 'qty' => 0, 'price' => -1, 'cost_internal' => -1, 'comment' => ''],
            ['id' => 2, 'status' => 'zaakceptowany']
        );
        $this->assertSame(5, count($validationErrors), 'Read-only accepted estimate items should reject invalid edits and locked state.');

        echo "Assertions: {$this->assertions}\n";
        echo "Estimate service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            throw new RuntimeException($message . " Expected: " . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new EstimateServiceTestRunner())->run();
