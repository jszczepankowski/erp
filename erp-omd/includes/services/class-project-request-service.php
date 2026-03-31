<?php

class ERP_OMD_Project_Request_Service
{
    private $clients;
    private $employees;
    private $estimates;
    private $projects;
    private $client_project_service;

    public function __construct(
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Client_Project_Service $client_project_service
    ) {
        $this->clients = $clients;
        $this->employees = $employees;
        $this->estimates = $estimates;
        $this->projects = $projects;
        $this->client_project_service = $client_project_service;
    }

    public function prepare(array $data, array $existing = null)
    {
        return [
            'requester_user_id' => (int) ($data['requester_user_id'] ?? ($existing['requester_user_id'] ?? 0)),
            'requester_employee_id' => (int) ($data['requester_employee_id'] ?? ($existing['requester_employee_id'] ?? 0)),
            'client_id' => (int) ($data['client_id'] ?? ($existing['client_id'] ?? 0)),
            'project_name' => trim((string) ($data['project_name'] ?? ($existing['project_name'] ?? ''))),
            'billing_type' => trim((string) ($data['billing_type'] ?? ($existing['billing_type'] ?? 'time_material'))) ?: 'time_material',
            'preferred_manager_id' => (int) ($data['preferred_manager_id'] ?? ($existing['preferred_manager_id'] ?? 0)),
            'estimate_id' => (int) ($data['estimate_id'] ?? ($existing['estimate_id'] ?? 0)),
            'brief' => trim((string) ($data['brief'] ?? ($existing['brief'] ?? ''))),
            'start_date' => trim((string) ($data['start_date'] ?? ($existing['start_date'] ?? ''))),
            'end_date' => trim((string) ($data['end_date'] ?? ($existing['end_date'] ?? ''))),
            'status' => trim((string) ($data['status'] ?? ($existing['status'] ?? 'new'))) ?: 'new',
            'reviewed_by_user_id' => (int) ($data['reviewed_by_user_id'] ?? ($existing['reviewed_by_user_id'] ?? 0)),
            'reviewed_at' => $data['reviewed_at'] ?? ($existing['reviewed_at'] ?? null),
            'converted_project_id' => (int) ($data['converted_project_id'] ?? ($existing['converted_project_id'] ?? 0)),
        ];
    }

    public function validate(array $data, array $existing = null)
    {
        $data = $this->prepare($data, $existing);
        $errors = [];

        if ($data['requester_user_id'] <= 0) {
            $errors[] = __('Wniosek musi być powiązany z użytkownikiem zgłaszającym.', 'erp-omd');
        }

        if (! $this->employees->find((int) $data['requester_employee_id'])) {
            $errors[] = __('Wniosek musi być powiązany z istniejącym pracownikiem.', 'erp-omd');
        }

        if (! $this->clients->find((int) $data['client_id'])) {
            $errors[] = __('Wniosek musi wskazywać istniejącego klienta.', 'erp-omd');
        }

        if ($data['project_name'] === '') {
            $errors[] = __('Nazwa projektu we wniosku jest wymagana.', 'erp-omd');
        }

        if (! in_array($data['billing_type'], ['time_material', 'fixed_price', 'retainer'], true)) {
            $errors[] = __('Typ rozliczenia wniosku jest niepoprawny.', 'erp-omd');
        }

        if ($data['preferred_manager_id'] > 0 && ! $this->employees->find((int) $data['preferred_manager_id'])) {
            $errors[] = __('Preferowany manager musi wskazywać istniejącego pracownika.', 'erp-omd');
        }

        if ($data['estimate_id'] > 0 && ! $this->estimates->find((int) $data['estimate_id'])) {
            $errors[] = __('Powiązany kosztorys nie istnieje.', 'erp-omd');
        }

        if ($data['start_date'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
            $errors[] = __('Data rozpoczęcia wniosku jest niepoprawna.', 'erp-omd');
        }

        if ($data['end_date'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
            $errors[] = __('Data zakończenia wniosku jest niepoprawna.', 'erp-omd');
        }

        if ($data['start_date'] !== '' && $data['end_date'] !== '' && $data['start_date'] > $data['end_date']) {
            $errors[] = __('Data zakończenia nie może być wcześniejsza niż data rozpoczęcia.', 'erp-omd');
        }

        if (! in_array($data['status'], ['new', 'under_review', 'approved', 'rejected', 'converted'], true)) {
            $errors[] = __('Status wniosku projektowego jest niepoprawny.', 'erp-omd');
        }

        if ($existing && ! $this->can_transition_status((string) ($existing['status'] ?? 'new'), $data['status'])) {
            $errors[] = __('Niedozwolona zmiana statusu wniosku projektowego.', 'erp-omd');
        }

        if ($data['status'] === 'converted' && (int) $data['converted_project_id'] <= 0) {
            $errors[] = __('Wniosek w statusie converted musi wskazywać utworzony projekt.', 'erp-omd');
        }

        return array_values(array_unique($errors));
    }

    public function can_transition_status($current_status, $target_status)
    {
        if ($current_status === $target_status) {
            return true;
        }

        $allowed = [
            'new' => ['under_review', 'approved', 'rejected'],
            'under_review' => ['approved', 'rejected', 'new'],
            'approved' => ['converted', 'under_review', 'rejected', 'new'],
            'rejected' => ['under_review', 'approved', 'new'],
            'converted' => [],
        ];

        return in_array($target_status, $allowed[$current_status] ?? [], true);
    }

    public function build_project_payload(array $request)
    {
        return $this->client_project_service->prepare_project([
            'client_id' => (int) ($request['client_id'] ?? 0),
            'name' => (string) ($request['project_name'] ?? ''),
            'billing_type' => (string) ($request['billing_type'] ?? 'time_material'),
            'budget' => 0,
            'retainer_monthly_fee' => 0,
            'status' => 'do_rozpoczecia',
            'manager_id' => (int) ($request['preferred_manager_id'] ?? 0),
            'manager_ids' => array_values(array_filter([(int) ($request['preferred_manager_id'] ?? 0)])),
            'estimate_id' => (int) ($request['estimate_id'] ?? 0),
            'brief' => (string) ($request['brief'] ?? ''),
            'start_date' => (string) ($request['start_date'] ?? ''),
            'end_date' => (string) ($request['end_date'] ?? ''),
        ]);
    }

    public function validate_conversion(array $request)
    {
        $request = $this->prepare($request);
        $errors = [];

        if (! in_array($request['status'], ['approved', 'converted'], true)) {
            $errors[] = __('Tylko zatwierdzony wniosek można skonwertować do projektu.', 'erp-omd');
        }

        $project_payload = $this->build_project_payload($request);
        $errors = array_merge($errors, $this->client_project_service->validate_project($project_payload));

        return array_values(array_unique($errors));
    }
}
