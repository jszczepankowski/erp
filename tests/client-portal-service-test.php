<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text)
    {
        return $text;
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-client-portal-service.php';

class ERP_OMD_Project_Repository_Fake
{
    public function find($id)
    {
        if ((int) $id !== 77) {
            return null;
        }

        return ['id' => 77, 'name' => 'Portal Project', 'budget' => 1000, 'created_at' => '2026-04-01 10:00:00'];
    }
}

class ERP_OMD_Project_Revenue_Repository_Fake
{
    public function for_project($project_id)
    {
        return [
            ['id' => 1, 'description' => 'Change request A', 'amount' => 200, 'revenue_date' => '2026-04-10'],
            ['id' => 2, 'description' => 'Change request B', 'amount' => 300, 'revenue_date' => '2026-04-12'],
        ];
    }
}

class ERP_OMD_Project_Cost_Repository_Fake
{
    public function for_project($project_id)
    {
        return [
            ['id' => 1, 'description' => 'Zakup licencji', 'amount' => 120, 'cost_date' => '2026-04-11'],
            ['id' => 2, 'description' => 'Podwykonawca', 'amount' => 80, 'cost_date' => '2026-04-13'],
        ];
    }
}

$service = new ERP_OMD_Client_Portal_Service(
    new ERP_OMD_Project_Repository_Fake(),
    new ERP_OMD_Project_Revenue_Repository_Fake(),
    new ERP_OMD_Project_Cost_Repository_Fake()
);

$assertions = 0;
$view = $service->build_project_finance_view(77);

$assertions++;
if (! is_array($view) || (int) ($view['project_id'] ?? 0) !== 77) {
    throw new RuntimeException('Expected finance view for project 77.');
}

$assertions++;
if ((float) ($view['budget_current'] ?? 0) !== 1500.0) {
    throw new RuntimeException('Expected budget_current to include budget increases.');
}

$assertions++;
if ((float) ($view['cost_total'] ?? 0) !== 200.0) {
    throw new RuntimeException('Expected cost_total based on visible cost items.');
}

$assertions++;
if (count((array) ($view['budget_history'] ?? [])) < 3) {
    throw new RuntimeException('Expected budget history with base budget and increases.');
}

echo "Assertions: {$assertions}\n";
echo "Client portal service test passed.\n";
