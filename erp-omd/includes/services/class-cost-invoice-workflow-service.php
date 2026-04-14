<?php

class ERP_OMD_Cost_Invoice_Workflow_Service
{
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
        $status = $this->normalize_status((string) ($invoice_data['status'] ?? ''));

        if ($supplier_id <= 0) {
            $errors[] = __('Wybierz dostawcę dla faktury kosztowej.', 'erp-omd');
        }

        if ($project_id <= 0) {
            $errors[] = __('Wybierz projekt dla faktury kosztowej.', 'erp-omd');
        }

        if ($invoice_number === '') {
            $errors[] = __('Numer faktury kosztowej jest wymagany.', 'erp-omd');
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
}
