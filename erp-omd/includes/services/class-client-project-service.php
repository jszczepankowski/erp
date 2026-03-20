<?php

class ERP_OMD_Client_Project_Service
{
    private $clients;
    private $employees;
    private $roles;

    public function __construct(ERP_OMD_Client_Repository $clients, ERP_OMD_Employee_Repository $employees, ERP_OMD_Role_Repository $roles)
    {
        $this->clients = $clients;
        $this->employees = $employees;
        $this->roles = $roles;
    }

    public function validate_client(array $data, $client_id = null)
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = __('Nazwa klienta jest wymagana.', 'erp-omd');
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

        if (! in_array($data['status'], ['active', 'inactive'], true)) {
            $errors[] = __('Status klienta musi być active lub inactive.', 'erp-omd');
        }

        if (! empty($data['account_manager_id']) && ! $this->employees->find((int) $data['account_manager_id'])) {
            $errors[] = __('Account manager musi wskazywać istniejącego pracownika.', 'erp-omd');
        }

        return $errors;
    }

    public function validate_client_rate($client_id, $role_id, $rate)
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

        return $errors;
    }

    public function validate_project(array $data)
    {
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

        if (! in_array($data['status'], ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'], true)) {
            $errors[] = __('Status projektu jest niepoprawny.', 'erp-omd');
        }

        if ((float) $data['budget'] < 0) {
            $errors[] = __('Budżet projektu nie może być ujemny.', 'erp-omd');
        }

        if ((float) $data['retainer_monthly_fee'] < 0) {
            $errors[] = __('Miesięczna opłata retainer nie może być ujemna.', 'erp-omd');
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

        if (! empty($data['manager_id']) && ! $this->employees->find((int) $data['manager_id'])) {
            $errors[] = __('Manager projektu musi wskazywać istniejącego pracownika.', 'erp-omd');
        }

        return $errors;
    }

    private function valid_date($date)
    {
        $normalized = DateTimeImmutable::createFromFormat('Y-m-d', (string) $date);
        return $normalized && $normalized->format('Y-m-d') === $date;
    }
}
