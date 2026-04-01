<?php

class ERP_OMD_Client_Project_Service
{
    private $clients;
    private $employees;
    private $roles;
    private $projects;
    private $time_entries;
    private $alert_service;

    public function __construct(
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Project_Repository $projects = null,
        ERP_OMD_Time_Entry_Repository $time_entries = null,
        ERP_OMD_Alert_Service $alert_service = null
    ) {
        $this->clients = $clients;
        $this->employees = $employees;
        $this->roles = $roles;
        $this->projects = $projects;
        $this->time_entries = $time_entries;
        $this->alert_service = $alert_service;
    }

    public function prepare_client(array $data)
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['company'] = trim((string) ($data['company'] ?? ''));
        $data['nip'] = $this->normalize_nip((string) ($data['nip'] ?? ''));
        $data['email'] = trim((string) ($data['email'] ?? ''));
        $data['phone'] = $this->normalize_phone((string) ($data['phone'] ?? ''));
        $data['contact_person_name'] = trim((string) ($data['contact_person_name'] ?? ''));
        $data['contact_person_email'] = trim((string) ($data['contact_person_email'] ?? ''));
        $data['contact_person_phone'] = $this->normalize_phone((string) ($data['contact_person_phone'] ?? ''));
        $data['city'] = trim((string) ($data['city'] ?? ''));
        $data['street'] = trim((string) ($data['street'] ?? ''));
        $data['apartment_number'] = trim((string) ($data['apartment_number'] ?? ''));
        $data['postal_code'] = $this->normalize_postal_code((string) ($data['postal_code'] ?? ''));
        $data['country'] = $this->normalize_country((string) ($data['country'] ?? ''));
        $data['status'] = trim((string) ($data['status'] ?? 'active')) ?: 'active';
        $data['account_manager_id'] = (int) ($data['account_manager_id'] ?? 0);
        $data['alert_margin_threshold'] = $this->normalize_margin_threshold($data['alert_margin_threshold'] ?? null);

        return $data;
    }

    public function validate_client(array $data, $client_id = null)
    {
        $data = $this->prepare_client($data);
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = __('Nazwa klienta jest wymagana.', 'erp-omd');
        }

        if ($data['nip'] !== '' && strlen($data['nip']) !== 10) {
            $errors[] = __('NIP klienta musi zawierać dokładnie 10 cyfr.', 'erp-omd');
        }

        if ($data['nip'] !== '' && $this->clients->nip_exists($data['nip'], $client_id ? (int) $client_id : null)) {
            $errors[] = __('NIP klienta musi być unikalny.', 'erp-omd');
        }

        if ($data['email'] !== '' && ! is_email($data['email'])) {
            $errors[] = __('Adres e-mail klienta jest niepoprawny.', 'erp-omd');
        }

        if ($data['contact_person_email'] !== '' && ! is_email($data['contact_person_email'])) {
            $errors[] = __('Adres e-mail osoby kontaktowej jest niepoprawny.', 'erp-omd');
        }

        if ($data['phone'] !== '' && ! $this->is_valid_phone($data['phone'])) {
            $errors[] = __('Telefon klienta jest niepoprawny.', 'erp-omd');
        }

        if ($data['contact_person_phone'] !== '' && ! $this->is_valid_phone($data['contact_person_phone'])) {
            $errors[] = __('Telefon osoby kontaktowej jest niepoprawny.', 'erp-omd');
        }

        if ($data['postal_code'] !== '' && ! preg_match('/^[0-9]{2}-[0-9]{3}$/', $data['postal_code'])) {
            $errors[] = __('Kod pocztowy musi mieć format 00-000.', 'erp-omd');
        }

        if (! in_array($data['status'], ['active', 'inactive'], true)) {
            $errors[] = __('Status klienta musi być active lub inactive.', 'erp-omd');
        }

        if ($data['alert_margin_threshold'] !== null && $data['alert_margin_threshold'] < 0) {
            $errors[] = __('Nadpisanie progu marży klienta nie może być ujemne.', 'erp-omd');
        }

        if (! empty($data['account_manager_id']) && ! $this->employees->find((int) $data['account_manager_id'])) {
            $errors[] = __('Account manager musi wskazywać istniejącego pracownika.', 'erp-omd');
        }

        return $errors;
    }

    public function validate_client_rate($client_id, $role_id, $rate, $valid_from = '', $valid_to = '')
    {
        $errors = [];

        if (! $this->clients->find((int) $client_id)) {
            $errors[] = __('Nie znaleziono klienta dla stawki.', 'erp-omd');
        }

        if ((int) $role_id <= 0) {
            $errors[] = __('Wybierz rolę dla stawki klienta.', 'erp-omd');
        } elseif (! $this->roles->find((int) $role_id)) {
            $errors[] = __('Wybrana rola klienta nie istnieje.', 'erp-omd');
        }

        if ((float) $rate < 0) {
            $errors[] = __('Stawka klienta nie może być ujemna.', 'erp-omd');
        }

        $errors = array_merge($errors, $this->validate_effective_dates($valid_from, $valid_to));

        return $errors;
    }

    public function validate_project(array $data, array $existing_project = null)
    {
        $data = $this->prepare_project($data, $existing_project);
        $errors = [];

        if (! $this->clients->find((int) $data['client_id'])) {
            $errors[] = __('Projekt musi być przypisany do istniejącego klienta.', 'erp-omd');
        }

        if ($data['name'] === '') {
            $errors[] = __('Nazwa projektu jest wymagana.', 'erp-omd');
        }

        if (! in_array($data['billing_type'], ['time_material', 'fixed_price', 'retainer'], true)) {
            $errors[] = __('Typ rozliczenia projektu jest niepoprawny.', 'erp-omd');
        }

        if (! in_array($data['status'], ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'archiwum'], true)) {
            $errors[] = __('Status projektu jest niepoprawny.', 'erp-omd');
        }

        if ((float) $data['budget'] < 0) {
            $errors[] = __('Budżet projektu nie może być ujemny.', 'erp-omd');
        }

        if ((float) $data['retainer_monthly_fee'] < 0) {
            $errors[] = __('Miesięczna opłata retainer nie może być ujemna.', 'erp-omd');
        }

        if ($data['alert_margin_threshold'] !== null && $data['alert_margin_threshold'] < 0) {
            $errors[] = __('Nadpisanie progu marży projektu nie może być ujemne.', 'erp-omd');
        }

        if ($data['start_date'] !== '' && ! $this->valid_date($data['start_date'])) {
            $errors[] = __('Data start_date jest niepoprawna.', 'erp-omd');
        }

        if ($data['end_date'] !== '' && ! $this->valid_date($data['end_date'])) {
            $errors[] = __('Data end_date jest niepoprawna.', 'erp-omd');
        }

        if ($data['start_date'] !== '' && $data['end_date'] !== '' && $data['end_date'] < $data['start_date']) {
            $errors[] = __('Data end_date nie może być wcześniejsza niż start_date.', 'erp-omd');
        }

        if (($data['operational_close_month'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}$/', (string) $data['operational_close_month']) !== 1) {
            $errors[] = __('Pole operational_close_month musi mieć format YYYY-MM.', 'erp-omd');
        }

        if (! empty($data['manager_id']) && ! $this->employees->find((int) $data['manager_id'])) {
            $errors[] = __('Manager projektu musi wskazywać istniejącego pracownika.', 'erp-omd');
        }

        foreach ((array) ($data['manager_ids'] ?? []) as $manager_id) {
            if (! $this->employees->find((int) $manager_id)) {
                $errors[] = __('Każdy manager projektu musi wskazywać istniejącego pracownika.', 'erp-omd');
                break;
            }
        }

        $errors = array_merge($errors, $this->validate_billing_policy($data));
        $errors = array_merge($errors, $this->validate_status_transition($data, $existing_project));

        return array_values(array_unique($errors));
    }

    public function prepare_project(array $data, array $existing_project = null)
    {
        $manager_ids = $this->prepare_manager_ids($data, $existing_project);
        $manager_id = (int) ($data['manager_id'] ?? ($existing_project['manager_id'] ?? 0));
        if ($manager_id <= 0 && ! empty($manager_ids)) {
            $manager_id = (int) $manager_ids[0];
        }

        return [
            'client_id' => (int) ($data['client_id'] ?? ($existing_project['client_id'] ?? 0)),
            'name' => trim((string) ($data['name'] ?? ($existing_project['name'] ?? ''))),
            'billing_type' => trim((string) ($data['billing_type'] ?? ($existing_project['billing_type'] ?? 'time_material'))) ?: 'time_material',
            'budget' => round((float) ($data['budget'] ?? ($existing_project['budget'] ?? 0)), 2),
            'retainer_monthly_fee' => round((float) ($data['retainer_monthly_fee'] ?? ($existing_project['retainer_monthly_fee'] ?? 0)), 2),
            'status' => trim((string) ($data['status'] ?? ($existing_project['status'] ?? 'do_rozpoczecia'))) ?: 'do_rozpoczecia',
            'start_date' => trim((string) ($data['start_date'] ?? ($existing_project['start_date'] ?? ''))),
            'end_date' => trim((string) ($data['end_date'] ?? ($existing_project['end_date'] ?? ''))),
            'operational_close_month' => trim((string) ($data['operational_close_month'] ?? ($existing_project['operational_close_month'] ?? ''))),
            'manager_id' => $manager_id,
            'manager_ids' => $manager_ids,
            'estimate_id' => (int) ($data['estimate_id'] ?? ($existing_project['estimate_id'] ?? 0)),
            'brief' => trim((string) ($data['brief'] ?? ($existing_project['brief'] ?? ''))),
            'alert_margin_threshold' => $this->normalize_margin_threshold($data['alert_margin_threshold'] ?? ($existing_project['alert_margin_threshold'] ?? null)),
        ];
    }


    private function prepare_manager_ids(array $data, array $existing_project = null)
    {
        $incoming_manager_ids = $data['manager_ids'] ?? ($existing_project['manager_ids'] ?? []);
        if (! is_array($incoming_manager_ids)) {
            $incoming_manager_ids = [];
        }

        $manager_id = (int) ($data['manager_id'] ?? ($existing_project['manager_id'] ?? 0));
        $manager_ids = array_values(array_unique(array_filter(array_map('intval', $incoming_manager_ids))));

        if ($manager_id > 0 && ! in_array($manager_id, $manager_ids, true)) {
            array_unshift($manager_ids, $manager_id);
        }

        if ($manager_id <= 0 && ! empty($manager_ids)) {
            $manager_id = (int) $manager_ids[0];
        }

        if ($manager_id > 0) {
            $data['manager_id'] = $manager_id;
        }

        return array_values(array_unique(array_filter($manager_ids)));
    }

    private function validate_billing_policy(array $data)
    {
        $errors = [];

        switch ($data['billing_type']) {
            case 'fixed_price':
                if ((float) $data['budget'] <= 0) {
                    $errors[] = __('Projekt fixed_price wymaga dodatniego budżetu.', 'erp-omd');
                }
                if ((float) $data['retainer_monthly_fee'] !== 0.0) {
                    $errors[] = __('Projekt fixed_price nie może mieć opłaty retainer.', 'erp-omd');
                }
                break;
            case 'time_material':
                if ((float) $data['retainer_monthly_fee'] !== 0.0) {
                    $errors[] = __('Projekt time_material nie może mieć opłaty retainer.', 'erp-omd');
                }
                break;
            case 'retainer':
                if ((float) $data['retainer_monthly_fee'] <= 0) {
                    $errors[] = __('Projekt retainer wymaga dodatniej opłaty miesięcznej.', 'erp-omd');
                }
                if ((float) $data['budget'] !== 0.0) {
                    $errors[] = __('Projekt retainer nie powinien mieć budżetu fixed price — ustaw 0.', 'erp-omd');
                }
                break;
        }

        return $errors;
    }

    private function validate_status_transition(array $data, array $existing_project = null)
    {
        $errors = [];

        if (! $existing_project) {
            return $errors;
        }

        $current_status = (string) ($existing_project['status'] ?? '');
        $target_status = (string) ($data['status'] ?? '');

        if ($current_status === '' || $current_status === $target_status) {
            return $errors;
        }

        $allowed_transitions = [
            'do_rozpoczecia' => ['w_realizacji', 'archiwum'],
            'w_realizacji' => ['w_akceptacji', 'do_faktury', 'archiwum'],
            'w_akceptacji' => ['w_realizacji', 'do_faktury', 'archiwum'],
            'do_faktury' => ['zakonczony', 'w_realizacji', 'archiwum'],
            'zakonczony' => ['archiwum'],
            'archiwum' => ['do_rozpoczecia'],
        ];

        if (! in_array($target_status, $allowed_transitions[$current_status] ?? [], true)) {
            $errors[] = __('Niedozwolona zmiana statusu projektu.', 'erp-omd');
            return $errors;
        }

        if ($target_status === 'do_faktury') {
            $project_id = (int) ($existing_project['id'] ?? 0);
            if ($project_id > 0 && $this->time_entries && $this->time_entries->count_for_project_by_statuses($project_id, ['submitted', 'rejected']) > 0) {
                $errors[] = __('Projekt nie może przejść do do_faktury, jeśli ma niezatwierdzone wpisy czasu.', 'erp-omd');
            }

            if ($project_id > 0 && $this->alert_service) {
                $critical_alerts = array_filter(
                    $this->alert_service->alerts_for_entity('project', $project_id),
                    static function ($alert) {
                        return (string) ($alert['severity'] ?? '') === 'error';
                    }
                );

                if (! empty($critical_alerts)) {
                    $errors[] = __('Projekt nie może przejść do do_faktury, jeśli ma aktywne alerty krytyczne.', 'erp-omd');
                }
            }
        }

        return $errors;
    }

    private function validate_effective_dates($valid_from, $valid_to)
    {
        $errors = [];

        if ($valid_from !== '' && ! $this->valid_date($valid_from)) {
            $errors[] = __('Data valid_from jest niepoprawna.', 'erp-omd');
        }

        if ($valid_to !== '' && ! $this->valid_date($valid_to)) {
            $errors[] = __('Data valid_to jest niepoprawna.', 'erp-omd');
        }

        if ($valid_from !== '' && $valid_to !== '' && $valid_to < $valid_from) {
            $errors[] = __('Data valid_to nie może być wcześniejsza niż valid_from.', 'erp-omd');
        }

        return $errors;
    }

    private function normalize_nip($value)
    {
        return preg_replace('/\D+/', '', trim((string) $value));
    }

    private function normalize_phone($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $normalized = preg_replace('/[^0-9+]/', '', $value);
        if ($normalized !== '' && $normalized[0] !== '+' && strlen($normalized) >= 9) {
            return '+' . $normalized;
        }

        return $normalized;
    }

    private function is_valid_phone($value)
    {
        return (bool) preg_match('/^\+?[0-9]{9,15}$/', (string) $value);
    }

    private function normalize_postal_code($value)
    {
        $value = preg_replace('/\s+/', '', trim((string) $value));
        if (preg_match('/^[0-9]{5}$/', $value)) {
            return substr($value, 0, 2) . '-' . substr($value, 2, 3);
        }

        return $value;
    }

    private function normalize_country($value)
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return 'PL';
        }

        return substr($value, 0, 2);
    }

    private function normalize_margin_threshold($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, round((float) $value, 2));
    }

    private function valid_date($date)
    {
        $normalized = DateTimeImmutable::createFromFormat('Y-m-d', (string) $date);
        return $normalized && $normalized->format('Y-m-d') === $date;
    }
}
