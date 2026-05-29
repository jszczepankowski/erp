<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

class ERP_OMD_Project_Repository { private $rows; public function __construct(array $rows){$this->rows=$rows;} public function all(){return $this->rows;} public function find($id){foreach($this->rows as $r){ if((int)$r['id']===(int)$id){return $r;}} return null;} }
class ERP_OMD_Client_Repository { public function all(){ return []; } }
class ERP_OMD_Employee_Repository { public function all(){ return []; } }
class ERP_OMD_Salary_History_Repository { public function for_employees(array $ids){ return []; } }
class ERP_OMD_Project_Cost_Repository {
    private $rows; public function __construct(array $rows){$this->rows=$rows;}
    public function for_project($project_id){ return $this->rows[(int)$project_id] ?? []; }
    public function sum_by_project_and_month_in_date_range(array $project_ids, $from, $to){
        $out=[]; foreach($project_ids as $pid){ foreach(($this->rows[(int)$pid]??[]) as $r){ $d=(string)$r['cost_date']; if($d<(string)$from||$d>(string)$to) continue; $m=substr($d,0,7); $k=$m.':'.$pid; if(!isset($out[$k])) $out[$k]=['project_id'=>(int)$pid,'cost_month'=>$m,'amount_sum'=>0.0]; $out[$k]['amount_sum']+=(float)$r['amount']; }}
        return array_values($out);
    }
}
class ERP_OMD_Project_Revenue_Repository { private $rows; public function __construct(array $rows){$this->rows=$rows;} public function for_project($project_id){ return $this->rows[(int)$project_id] ?? []; } }
class ERP_OMD_Time_Entry_Repository { private $rows; public function __construct(array $rows){$this->rows=$rows;} public function all(array $filters=[]){ return $this->rows; } }
class ERP_OMD_Project_Financial_Service { public function get_project_financials(array $ids){ return []; } }

require_once __DIR__ . '/../erp-omd/includes/services/class-reporting-service-v2.php';

$service = new ERP_OMD_Reporting_Service(
    new ERP_OMD_Project_Repository([
        ['id'=>1,'client_id'=>1,'name'=>'A','client_name'=>'C1','status'=>'w_realizacji','billing_type'=>'time_material','manager_login'=>'m1','budget'=>0,'start_date'=>'2026-03-01','end_date'=>'2026-03-31'],
        ['id'=>2,'client_id'=>1,'name'=>'B','client_name'=>'C1','status'=>'zakonczony','billing_type'=>'fixed_price','manager_login'=>'m2','budget'=>1000,'start_date'=>'2026-03-01','end_date'=>'2026-03-31'],
    ]),
    new ERP_OMD_Client_Repository(),
    new ERP_OMD_Employee_Repository(),
    new ERP_OMD_Salary_History_Repository(),
    new ERP_OMD_Project_Cost_Repository([
        1 => [['amount'=>100,'cost_date'=>'2026-03-10']],
        2 => [['amount'=>200,'cost_date'=>'2026-03-11']],
    ]),
    new ERP_OMD_Project_Revenue_Repository([
        1 => [['amount'=>50,'revenue_date'=>'2026-03-09']],
        2 => [['amount'=>300,'revenue_date'=>'2026-03-08']],
    ]),
    new ERP_OMD_Time_Entry_Repository([
        ['project_id'=>1,'client_id'=>1,'employee_id'=>1,'hours'=>2,'entry_date'=>'2026-03-10','status'=>'approved','rate_snapshot'=>100,'cost_snapshot'=>40],
    ]),
    new ERP_OMD_Project_Financial_Service()
);

$closed = $service->build_report('omd_rozliczenia', ['report_type'=>'omd_rozliczenia','month'=>'2026-03','status'=>'omd_zakonczone']);
$current = $service->build_report('omd_rozliczenia', ['report_type'=>'omd_rozliczenia','month'=>'2026-03','status'=>'omd_biezace']);

if ($closed === [] || $current === []) { throw new RuntimeException('Expected non-empty rows for both status filters.'); }
$closedByMonth = array_column($closed, null, 'month');
$currentByMonth = array_column($current, null, 'month');
$closedMarch = $closedByMonth['2026-03'] ?? [];
$currentMarch = $currentByMonth['2026-03'] ?? [];
$closedRevenue = (float)($closedMarch['project_revenue'] ?? 0.0);
$currentRevenue = (float)($currentMarch['project_revenue'] ?? 0.0);
$closedDirectCost = (float)($closedMarch['project_direct_cost'] ?? 0.0);
$currentDirectCost = (float)($currentMarch['project_direct_cost'] ?? 0.0);

if (abs($closedRevenue - $currentRevenue) < 0.00001 && abs($closedDirectCost - $currentDirectCost) < 0.00001) {
    throw new RuntimeException('OMD status filters regression: expected different aggregates for different OMD status groups.');
}

echo "OMD status filter regression test passed.\n";
