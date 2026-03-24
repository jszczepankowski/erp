<?php

class ERP_OMD_Employee_Service
{
    /** @var ERP_OMD_Employee_Repository */
    private $employees;

    /** @var ERP_OMD_Salary_History_Repository */
    private $salary_history;

    /** @var ERP_OMD_Monthly_Hours_Service */
    private $monthly_hours;

    public function __construct(
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Monthly_Hours_Service $monthly_hours
    ) {
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->monthly_hours = $monthly_hours;
    }

    public function validate_employee(array $data, $employee_id = null)
    {
        $errors = [];

        if (empty($data['user_id']) || ! get_user_by('id', (int) $data['user_id'])) {
            $errors[] = __('Wybierz poprawne konto WordPress.', 'erp-omd');
        }

        if ($this->employees->user_exists((int) $data['user_id'], $employee_id ? (int) $employee_id : null)) {
            $errors[] = __('To konto WordPress jest już przypisane do pracownika.', 'erp-omd');
        }

        if (empty($data['status']) || ! in_array($data['status'], ['active', 'inactive'], true)) {
            $errors[] = __('Status pracownika musi być active lub inactive.', 'erp-omd');
        }

        if (empty($data['account_type']) || ! in_array($data['account_type'], ['admin', 'manager', 'worker'], true)) {
            $errors[] = __('Typ konta musi być jednym z: admin, manager, worker.', 'erp-omd');
        }

        if (! empty($data['default_role_id']) && ! in_array((int) $data['default_role_id'], array_map('intval', $data['role_ids']), true)) {
            $errors[] = __('Domyślna rola musi znajdować się wśród przypisanych ról pracownika.', 'erp-omd');
        }

        return $errors;
    }

    public function validate_salary(array $data, $salary_id = null)
    {
        $errors = [];
        $salary = (float) $data['monthly_salary'];
        $hours = (float) $data['monthly_hours'];
        $valid_from = $data['valid_from'];
        $valid_to = $data['valid_to'];

        if ($salary < 0) {
            $errors[] = __('Pensja miesięczna nie może być ujemna.', 'erp-omd');
        }

        if ($hours <= 0) {
            $errors[] = __('Liczba godzin miesięcznych musi być większa od zera.', 'erp-omd');
        }

        if (! $this->valid_date($valid_from)) {
            $errors[] = __('Data valid_from jest niepoprawna.', 'erp-omd');
        }

        if ($valid_to && ! $this->valid_date($valid_to)) {
            $errors[] = __('Data valid_to jest niepoprawna.', 'erp-omd');
        }

        if ($valid_to && $valid_to < $valid_from) {
            $errors[] = __('Data valid_to nie może być wcześniejsza niż valid_from.', 'erp-omd');
        }

        if ($this->salary_history->overlaps((int) $data['employee_id'], $valid_from, $valid_to, $salary_id ? (int) $salary_id : null)) {
            $errors[] = __('Zakres salary history nachodzi na istniejący okres dla tego pracownika.', 'erp-omd');
        }

        return $errors;
    }

    public function prepare_salary_payload(array $data)
    {
        $data['monthly_salary'] = round((float) $data['monthly_salary'], 2);
        $data['monthly_hours'] = round((float) $data['monthly_hours'], 2);
        $data['hourly_cost'] = $this->monthly_hours->calculate_hourly_cost($data['monthly_salary'], $data['monthly_hours']);

        return $data;
    }

    private function valid_date($date)
    {
        $normalized = DateTimeImmutable::createFromFormat('Y-m-d', (string) $date);
        return $normalized && $normalized->format('Y-m-d') === $date;
    }
}
