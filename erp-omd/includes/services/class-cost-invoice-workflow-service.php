<?php

class ERP_OMD_Cost_Invoice_Workflow_Service
{
    /** @var mixed */
    private $invoice_repository;

    /** @var mixed */
    private $audit_repository;

    /** @var mixed */
    private $supplier_repository;

    /** @var mixed */
    private $project_repository;

    public function __construct($invoice_repository = null, $audit_repository = null, $supplier_repository = null, $project_repository = null)
    {
        $this->invoice_repository = $invoice_repository;
        $this->audit_repository = $audit_repository;
        $this->supplier_repository = $supplier_repository;
        $this->project_repository = $project_repository;
    }

    /**
     * @return array<int,string>
     */
    public function allowed_statuses()
    {
        return ['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana'];
    }

    /**
     * @param string $current_status
     * @param string $next_status
     * @return bool
     */
    public function can_transition($current_status, $next_status)
    {
        $current_status = $this->normalize_status($current_status);
        $next_status = $this->normalize_status($next_status);

        if ($current_status === '' || $next_status === '') {
            return false;
        }

        if ($current_status === $next_status) {
            return true;
        }

        $transitions = [
            'zaimportowana' => ['weryfikacja'],
            'weryfikacja' => ['zaimportowana', 'zatwierdzona'],
            'zatwierdzona' => ['weryfikacja', 'przypisana'],
            'przypisana' => ['zatwierdzona'],
        ];

        return in_array($next_status, $transitions[$current_status] ?? [], true);
    }

    /**
     * @param array<string,mixed> $invoice_data
     * @param array<int,array<string,mixed>> $existing_supplier_invoices
     * @param array<string,mixed>|null $before_state
     * @return array<int,string>
     */
    public function validate_invoice_data(array $invoice_data, array $existing_supplier_invoices = [], $before_state = null)
    {
        $errors = [];

        $supplier_id = (int) ($invoice_data['supplier_id'] ?? 0);
        $project_id = (int) ($invoice_data['project_id'] ?? 0);
        $invoice_number = trim((string) ($invoice_data['invoice_number'] ?? ''));
        $issue_date = trim((string) ($invoice_data['issue_date'] ?? ''));
        $status = $this->normalize_status((string) ($invoice_data['status'] ?? ''));
        $source = trim((string) ($invoice_data['source'] ?? 'manual'));
        $ksef_reference_number = trim((string) ($invoice_data['ksef_reference_number'] ?? ''));
        $net_amount = (float) ($invoice_data['net_amount'] ?? 0);
        $vat_amount = (float) ($invoice_data['vat_amount'] ?? 0);
        $gross_amount = (float) ($invoice_data['gross_amount'] ?? 0);

        if ($supplier_id <= 0) {
            $errors[] = __('Wybierz dostawcę dla faktury kosztowej.', 'erp-omd');
        } elseif (! $this->supplier_exists($supplier_id)) {
            $errors[] = __('Wybrany dostawca nie istnieje.', 'erp-omd');
        }

        $is_ksef_source = mb_strtolower($source) === 'ksef';
        if ($project_id <= 0 && ! $is_ksef_source) {
            $errors[] = __('Wybierz projekt dla faktury kosztowej.', 'erp-omd');
        } elseif ($project_id > 0 && ! $this->project_exists($project_id)) {
            $errors[] = __('Wybrany projekt nie istnieje.', 'erp-omd');
        }

        if ($invoice_number === '') {
            $errors[] = __('Numer faktury kosztowej jest wymagany.', 'erp-omd');
        }

        if ($issue_date === '') {
            $errors[] = __('Data wystawienia faktury kosztowej jest wymagana.', 'erp-omd');
        } elseif (! $this->is_valid_iso_date($issue_date)) {
            $errors[] = __('Data wystawienia faktury kosztowej musi mieć format RRRR-MM-DD.', 'erp-omd');
        }

        if ($status === '') {
            $errors[] = __('Status faktury kosztowej jest wymagany.', 'erp-omd');
        }

        if ($status !== '' && ! in_array($status, $this->allowed_statuses(), true)) {
            $errors[] = __('Status faktury kosztowej jest spoza wspieranego workflow.', 'erp-omd');
        }

        if (is_array($before_state) && $status !== '') {
            $previous_status = $this->normalize_status((string) ($before_state['status'] ?? ''));
            if ($previous_status !== '' && ! $this->can_transition($previous_status, $status)) {
                $errors[] = __('Niedozwolona zmiana statusu faktury kosztowej.', 'erp-omd');
            }

            if ($previous_status === 'przypisana' && $status === 'przypisana') {
                $before_project_id = (int) ($before_state['project_id'] ?? 0);
                $before_supplier_id = (int) ($before_state['supplier_id'] ?? 0);
                if ($before_project_id > 0 && $before_project_id !== $project_id) {
                    $errors[] = __('Aby zmienić projekt faktury, najpierw cofnij status z przypisana.', 'erp-omd');
                }
                if ($before_supplier_id > 0 && $before_supplier_id !== $supplier_id) {
                    $errors[] = __('Aby zmienić dostawcę faktury, najpierw cofnij status z przypisana.', 'erp-omd');
                }
            }
        }

        if ($invoice_number !== '' && $supplier_id > 0) {
            $current_id = (int) ($invoice_data['id'] ?? 0);
            foreach ($existing_supplier_invoices as $existing_invoice) {
                $existing_number = trim((string) ($existing_invoice['invoice_number'] ?? ''));
                if ($existing_number === '') {
                    continue;
                }

                if (mb_strtolower($existing_number) !== mb_strtolower($invoice_number)) {
                    continue;
                }

                $existing_id = (int) ($existing_invoice['id'] ?? 0);
                if ($current_id > 0 && $existing_id === $current_id) {
                    continue;
                }

                $errors[] = __('Numer faktury musi być unikalny w obrębie dostawcy.', 'erp-omd');
                break;
            }
        }

        if ($net_amount < 0 || $vat_amount < 0 || $gross_amount < 0) {
            $errors[] = __('Kwoty faktury kosztowej nie mogą być ujemne.', 'erp-omd');
        }

        if ($this->is_totals_mismatch($net_amount, $vat_amount, $gross_amount)) {
            $errors[] = __('Kwota brutto musi być równa netto + VAT.', 'erp-omd');
        }

        if (mb_strtolower($source) === 'ksef') {
            if ($ksef_reference_number !== '' && $this->ksef_reference_exists_on_other_invoice($ksef_reference_number, (int) ($invoice_data['id'] ?? 0))) {
                $errors[] = __('Numer referencyjny KSeF musi być unikalny.', 'erp-omd');
            }
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $before_state
     * @param array<string,mixed> $after_state
     * @param int $changed_by_user_id
     * @return array<int,array<string,mixed>>
     */
    public function build_critical_audit_entries(array $before_state, array $after_state, $changed_by_user_id)
    {
        $watched_fields = ['status', 'project_id', 'supplier_id', 'net_amount', 'vat_amount', 'gross_amount'];
        $entries = [];

        foreach ($watched_fields as $field) {
            $before_value = $before_state[$field] ?? null;
            $after_value = $after_state[$field] ?? null;

            if ($this->normalize_scalar($before_value) === $this->normalize_scalar($after_value)) {
                continue;
            }

            $entries[] = [
                'invoice_id' => (int) ($after_state['id'] ?? $before_state['id'] ?? 0),
                'field_name' => $field,
                'before_value' => $this->normalize_scalar($before_value),
                'after_value' => $this->normalize_scalar($after_value),
                'changed_by_user_id' => (int) $changed_by_user_id,
                'changed_at' => $this->now(),
            ];
        }

        return $entries;
    }

    /**
     * @param array<string,mixed> $invoice_data
     * @return array<string,mixed>
     */
    public function create_invoice(array $invoice_data)
    {
        $existing_supplier_invoices = $this->load_supplier_invoices((int) ($invoice_data['supplier_id'] ?? 0));
        $errors = $this->validate_invoice_data($invoice_data, $existing_supplier_invoices, null);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if (! $this->invoice_repository || ! method_exists($this->invoice_repository, 'create')) {
            return ['ok' => false, 'errors' => [__('Repozytorium faktur kosztowych nie jest dostępne.', 'erp-omd')]];
        }

        $invoice_id = (int) $this->invoice_repository->create($invoice_data);
        if ($invoice_id <= 0) {
            return ['ok' => false, 'errors' => [__('Nie udało się zapisać faktury kosztowej.', 'erp-omd')]];
        }

        return ['ok' => true, 'invoice_id' => $invoice_id, 'errors' => []];
    }

    /**
     * @param int $invoice_id
     * @param array<string,mixed> $invoice_data
     * @param int $changed_by_user_id
     * @return array<string,mixed>
     */
    public function update_invoice($invoice_id, array $invoice_data, $changed_by_user_id)
    {
        if (! $this->invoice_repository || ! method_exists($this->invoice_repository, 'find') || ! method_exists($this->invoice_repository, 'update')) {
            return ['ok' => false, 'errors' => [__('Repozytorium faktur kosztowych nie jest dostępne.', 'erp-omd')]];
        }

        $before = (array) $this->invoice_repository->find((int) $invoice_id);
        if ($before === []) {
            return ['ok' => false, 'errors' => [__('Nie znaleziono faktury kosztowej do aktualizacji.', 'erp-omd')]];
        }

        $after = array_merge($before, $invoice_data, ['id' => (int) $invoice_id]);
        $supplier_id = (int) ($after['supplier_id'] ?? 0);
        $existing_supplier_invoices = $this->load_supplier_invoices($supplier_id);
        $errors = $this->validate_invoice_data($after, $existing_supplier_invoices, $before);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $updated = $this->invoice_repository->update((int) $invoice_id, $after);
        if ($updated === false) {
            return ['ok' => false, 'errors' => [__('Nie udało się zaktualizować faktury kosztowej.', 'erp-omd')]];
        }

        $audit_rows = $this->build_critical_audit_entries($before, $after, (int) $changed_by_user_id);
        if ($audit_rows !== [] && $this->audit_repository && method_exists($this->audit_repository, 'insert_many')) {
            $this->audit_repository->insert_many($audit_rows);
        }

        return ['ok' => true, 'invoice_id' => (int) $invoice_id, 'audit_rows' => $audit_rows, 'errors' => []];
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function normalize_scalar($value)
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) (float) $value;
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($value);
        }

        return (string) json_encode($value);
    }

    /**
     * @param string $status
     * @return string
     */
    private function normalize_status($status)
    {
        return mb_strtolower(trim($status));
    }

    /**
     * @return string
     */
    private function now()
    {
        if (function_exists('current_time')) {
            return (string) current_time('mysql');
        }

        return gmdate('Y-m-d H:i:s');
    }

    private function is_valid_iso_date($date)
    {
        if (! is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        $timestamp = strtotime($date . ' 00:00:00');
        if ($timestamp === false) {
            return false;
        }

        return gmdate('Y-m-d', $timestamp) === $date;
    }

    private function is_totals_mismatch($net_amount, $vat_amount, $gross_amount)
    {
        $expected_gross = round((float) $net_amount + (float) $vat_amount, 2);
        $actual_gross = round((float) $gross_amount, 2);

        return abs($expected_gross - $actual_gross) > 0.01;
    }

    private function ksef_reference_exists_on_other_invoice($ksef_reference_number, $current_invoice_id)
    {
        if ($ksef_reference_number === '' || ! $this->invoice_repository || ! method_exists($this->invoice_repository, 'find_by_ksef_reference')) {
            return false;
        }

        $existing = $this->invoice_repository->find_by_ksef_reference($ksef_reference_number);
        if (! is_array($existing) || $existing === []) {
            return false;
        }

        return (int) ($existing['id'] ?? 0) !== (int) $current_invoice_id;
    }

    /**
     * @param int $supplier_id
     * @return array<int,array<string,mixed>>
     */
    private function load_supplier_invoices($supplier_id)
    {
        if ($supplier_id <= 0 || ! $this->invoice_repository || ! method_exists($this->invoice_repository, 'for_supplier')) {
            return [];
        }

        return (array) $this->invoice_repository->for_supplier($supplier_id);
    }

    /**
     * @param int $supplier_id
     * @return bool
     */
    private function supplier_exists($supplier_id)
    {
        if (! $this->supplier_repository || ! method_exists($this->supplier_repository, 'find')) {
            return true;
        }

        return is_array($this->supplier_repository->find((int) $supplier_id));
    }

    /**
     * @param int $project_id
     * @return bool
     */
    private function project_exists($project_id)
    {
        if (! $this->project_repository || ! method_exists($this->project_repository, 'find')) {
            return true;
        }

        return is_array($this->project_repository->find((int) $project_id));
    }
}
