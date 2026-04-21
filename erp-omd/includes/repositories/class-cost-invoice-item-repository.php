<?php

class ERP_OMD_Cost_Invoice_Item_Repository
{
    private function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_cost_invoice_items';
    }

    /**
     * @param int $invoice_id
     * @return array<int,array<string,mixed>>
     */
    public function for_invoice($invoice_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, invoice_id, line_no, item_name, qty, unit, unit_net_amount, net_amount, vat_rate, vat_amount, gross_amount, source_payload_json
                 FROM {$this->table_name()}
                 WHERE invoice_id = %d
                 ORDER BY line_no ASC, id ASC",
                (int) $invoice_id
            ),
            ARRAY_A
        );
    }

    /**
     * @param int $invoice_id
     * @param array<int,array<string,mixed>> $items
     * @return bool
     */
    public function replace_for_invoice($invoice_id, array $items)
    {
        global $wpdb;

        $invoice_id = (int) $invoice_id;
        if ($invoice_id <= 0) {
            return false;
        }

        $this->delete_for_invoice($invoice_id);

        $line_no = 1;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $source_payload = $item['source_payload'] ?? null;
            if (function_exists('wp_json_encode')) {
                $source_payload = wp_json_encode($source_payload);
            } else {
                $source_payload = json_encode($source_payload);
            }

            $wpdb->insert(
                $this->table_name(),
                [
                    'invoice_id' => $invoice_id,
                    'line_no' => max(1, (int) ($item['line_no'] ?? $line_no)),
                    'item_name' => (string) ($item['name'] ?? ''),
                    'qty' => (float) ($item['qty'] ?? 0),
                    'unit' => (string) ($item['unit'] ?? ''),
                    'unit_net_amount' => (float) ($item['unit_net_amount'] ?? 0),
                    'net_amount' => (float) ($item['net_amount'] ?? 0),
                    'vat_rate' => (float) ($item['vat_rate'] ?? 0),
                    'vat_amount' => (float) ($item['vat_amount'] ?? 0),
                    'gross_amount' => (float) ($item['gross_amount'] ?? 0),
                    'source_payload_json' => is_string($source_payload) ? $source_payload : '',
                ],
                ['%d', '%d', '%s', '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%s']
            );

            $line_no++;
        }

        return true;
    }

    /**
     * @param int $invoice_id
     * @return bool|int
     */
    public function delete_for_invoice($invoice_id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name(),
            ['invoice_id' => (int) $invoice_id],
            ['%d']
        );
    }
}
