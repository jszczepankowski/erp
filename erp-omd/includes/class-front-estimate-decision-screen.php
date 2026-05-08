<?php

if (! class_exists('ERP_OMD_Front_Estimate_Decision_Screen')) {
    class ERP_OMD_Front_Estimate_Decision_Screen
    {
        public static function handle_request(
            ERP_OMD_Estimate_Repository $estimates,
            ERP_OMD_Estimate_Item_Repository $estimate_items,
            ERP_OMD_Estimate_Service $estimate_service
        ) {
            $token = sanitize_text_field((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
            $notice_type = '';
            $notice_message = '';
            $decision_done = false;
            $selected_decision = 'accept';
            $comment_value = '';
            $note_value = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['erp_omd_front_action']) && wp_unslash((string) $_POST['erp_omd_front_action']) === 'estimate_decision') {
                check_admin_referer('erp_omd_front_estimate_decision');
                $decision = sanitize_key((string) wp_unslash($_POST['decision'] ?? ''));
                $comment = sanitize_textarea_field((string) wp_unslash($_POST['comment'] ?? ''));
                $note = sanitize_textarea_field((string) wp_unslash($_POST['note'] ?? ''));
                $delivery_other = ! empty($_POST['delivery_other']) ? 1 : 0;
                $delivery_address = sanitize_textarea_field((string) wp_unslash($_POST['delivery_address'] ?? ''));
                $preferred_delivery_date = sanitize_text_field((string) wp_unslash($_POST['preferred_delivery_date'] ?? ''));
                $invoice_other_entity = ! empty($_POST['invoice_other_entity']) ? 1 : 0;
                $invoice_nip = sanitize_text_field((string) wp_unslash($_POST['invoice_nip'] ?? ''));
                $selected_decision = in_array($decision, ['accept', 'reject'], true) ? $decision : 'accept';
                $comment_value = $comment;
                $note_value = $note;
                $state = self::resolve_state($estimates, $token);
                if ($state instanceof WP_Error) {
                    $notice_type = 'error';
                    $notice_message = $state->get_error_message();
                } elseif (! in_array($decision, ['accept', 'reject'], true)) {
                    $notice_type = 'error';
                    $notice_message = __('Niepoprawna decyzja.', 'erp-omd');
                } elseif ($decision === 'reject' && trim($comment) === '') {
                    $notice_type = 'error';
                    $notice_message = __('Komentarz jest wymagany przy odrzuceniu kosztorysu.', 'erp-omd');
                } else {
                    $estimate_id = (int) ($state['estimate']['id'] ?? 0);
                    if ($decision === 'accept') {
                        $result = $estimate_service->accept($estimate_id);
                        if ($result instanceof WP_Error) {
                            $notice_type = 'error';
                            $notice_message = $result->get_error_message();
                        } else {
                            self::finalize_acceptance(
                                $estimate_id,
                                $note,
                                [
                                    'delivery_other' => $delivery_other,
                                    'delivery_address' => $delivery_address,
                                    'preferred_delivery_date' => $preferred_delivery_date,
                                    'invoice_other_entity' => $invoice_other_entity,
                                    'invoice_nip' => $invoice_nip,
                                ],
                                $estimates,
                                $estimate_items,
                                $estimate_service,
                                (array) $result
                            );
                            $notice_type = 'success';
                            $notice_message = __('Dziękujemy. Kosztorys został zaakceptowany.', 'erp-omd');
                            $decision_done = true;
                            self::invalidate_token($estimate_id);
                        }
                    } else {
                        $estimate = (array) $state['estimate'];
                        $estimate['status'] = 'odrzucony';
                        $estimate['accepted_by_user_id'] = 0;
                        $estimate['accepted_at'] = null;
                        $estimate['client_decision_note'] = $comment;
                        $estimates->update($estimate_id, $estimate);
                        update_option('erp_omd_estimate_client_rejection_comment_' . $estimate_id, $comment, false);
                        $notice_type = 'success';
                        $notice_message = __('Dziękujemy. Kosztorys został odrzucony.', 'erp-omd');
                        $decision_done = true;
                        self::invalidate_token($estimate_id);
                    }
                }
            }

            $state = self::resolve_state($estimates, $token);
            if ($state instanceof WP_Error) {
                if ($notice_message === '') {
                    $notice_type = 'error';
                    $notice_message = $state->get_error_message();
                }
                $estimate = null;
                $estimate_items_rows = [];
                $estimate_totals = ['net' => 0, 'tax' => 0, 'gross' => 0];
                $token_expires_at = 0;
                $token_valid = false;
            } else {
                $estimate = (array) ($state['estimate'] ?? []);
                $estimate_id = (int) ($estimate['id'] ?? 0);
                $estimate_items_rows = $estimate_id > 0 ? $estimate_items->for_estimate($estimate_id) : [];
                $estimate_totals = $estimate_service->calculate_totals($estimate_items_rows);
                $token_expires_at = (int) ($state['token_row']['expires_at'] ?? 0);
                $token_valid = ! $decision_done;
            }

            $front_brand_label = __('ERP OMD FRONT', 'erp-omd');
            $decision_page_title = __('Decyzja klienta — kosztorys', 'erp-omd');
            $token_expiry_label = $token_expires_at > 0 ? wp_date('d.m.Y H:i', $token_expires_at) : '';
            $estimate_items = $estimate_items_rows;

            status_header(200);
            nocache_headers();
            include ERP_OMD_PATH . 'templates/front/estimate-decision.php';
            exit;
        }

        public static function finalize_acceptance(
            $estimate_id,
            $note,
            array $acceptance_meta,
            ERP_OMD_Estimate_Repository $estimates,
            ERP_OMD_Estimate_Item_Repository $estimate_items,
            ERP_OMD_Estimate_Service $estimate_service,
            array $accept_result = []
        ) {
            $estimate_id = (int) $estimate_id;
            $delivery_other = ! empty($acceptance_meta['delivery_other']) ? 1 : 0;
            $invoice_other_entity = ! empty($acceptance_meta['invoice_other_entity']) ? 1 : 0;
            $normalized_meta = [
                'delivery_other' => $delivery_other,
                'delivery_address' => $delivery_other ? sanitize_textarea_field((string) ($acceptance_meta['delivery_address'] ?? '')) : '',
                'preferred_delivery_date' => sanitize_text_field((string) ($acceptance_meta['preferred_delivery_date'] ?? '')),
                'invoice_other_entity' => $invoice_other_entity,
                'invoice_nip' => $invoice_other_entity ? sanitize_text_field((string) ($acceptance_meta['invoice_nip'] ?? '')) : '',
            ];
            $estimates->save_client_decision_note($estimate_id, sanitize_textarea_field((string) $note));
            update_option('erp_omd_estimate_acceptance_meta_' . $estimate_id, $normalized_meta, false);
            self::append_accept_note_to_project_history($estimate_id, $note, $accept_result);
            self::send_thank_you_mail($estimate_id, $estimates, $estimate_items, $estimate_service);
            self::send_acceptance_notification_to_agency($estimate_id, $estimates, $estimate_items, $estimate_service, $accept_result);
        }

        private static function send_thank_you_mail($estimate_id, ERP_OMD_Estimate_Repository $estimates, ERP_OMD_Estimate_Item_Repository $estimate_items, ERP_OMD_Estimate_Service $estimate_service)
        {
            $estimate = $estimates->find((int) $estimate_id);
            if (! $estimate) {
                return;
            }

            $client_repository = class_exists('ERP_OMD_Client_Repository') ? new ERP_OMD_Client_Repository() : null;
            if (! $client_repository || ! method_exists($client_repository, 'find')) {
                return;
            }
            $client = $client_repository->find((int) ($estimate['client_id'] ?? 0));
            $client_email_raw = (string) ($client['email'] ?? '');
            $client_emails = preg_split('/[,;\s]+/', $client_email_raw) ?: [];
            $client_emails = array_values(array_unique(array_filter(array_map('sanitize_email', $client_emails), 'is_email')));
            if ($client_emails === []) {
                return;
            }

            $items = $estimate_items->for_estimate((int) $estimate_id);
            $totals = $estimate_service->calculate_totals($items);
            $accept_meta = (array) get_option('erp_omd_estimate_acceptance_meta_' . (int) $estimate_id, []);
            $mail_defaults = [
                'subject' => __('[ERP OMD] Dziękujemy za akceptację kosztorysu {estimate_name}', 'erp-omd'),
                'body' => __('Dziękujemy za akceptację kosztorysu <strong>{estimate_name}</strong>.<br><br>Poniżej przesyłamy podsumowanie pozycji i finalnej kwoty.', 'erp-omd'),
            ];
            $mail_settings = wp_parse_args((array) get_option('erp_omd_estimate_client_thank_you_mail_settings', []), $mail_defaults);
            $tokens = [
                '{estimate_name}' => (string) ($estimate['name'] ?? ('#' . (int) $estimate_id)),
                '{client_name}' => (string) ($client['name'] ?? ($estimate['client_name'] ?? '')),
                '{final_net}' => number_format_i18n((float) ($totals['net'] ?? 0), 2),
                '{final_gross}' => number_format_i18n((float) ($totals['gross'] ?? 0), 2),
                '{client_note}' => (string) ($estimate['client_decision_note'] ?? ''),
            ];

            $subject = strtr((string) ($mail_settings['subject'] ?? $mail_defaults['subject']), $tokens);
            $body = strtr((string) ($mail_settings['body'] ?? $mail_defaults['body']), $tokens);
            $accept_meta_lines = [];
            if (! empty($accept_meta['preferred_delivery_date'])) {
                $accept_meta_lines[] = sprintf(__('Preferowany termin realizacji: %s', 'erp-omd'), (string) $accept_meta['preferred_delivery_date']);
            }
            if (! empty($accept_meta['delivery_address'])) {
                $accept_meta_lines[] = sprintf(__('Adres do dostawy: %s', 'erp-omd'), (string) $accept_meta['delivery_address']);
            }
            if (! empty($accept_meta['invoice_nip'])) {
                $accept_meta_lines[] = sprintf(__('NIP do faktury: %s', 'erp-omd'), (string) $accept_meta['invoice_nip']);
            }
            if ($accept_meta_lines !== []) {
                $body .= '<br><br><strong>' . esc_html__('Dodatkowe dane z akceptacji:', 'erp-omd') . '</strong><br>'
                    . implode('<br>', array_map('esc_html', $accept_meta_lines)) . '<br>';
            }
            $body .= self::build_summary_table_html($items, $totals, true);
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sender_name = sanitize_text_field((string) get_option('erp_omd_estimate_mail_sender_name', ''));
            $sender_email = sanitize_email((string) get_option('erp_omd_estimate_mail_sender_email', ''));
            if ($sender_email !== '' && is_email($sender_email)) {
                $from_email_filter = static function () use ($sender_email) {
                    return $sender_email;
                };
                $from_name_filter = static function () use ($sender_name) {
                    return $sender_name !== '' ? $sender_name : 'WordPress';
                };
                add_filter('wp_mail_from', $from_email_filter);
                add_filter('wp_mail_from_name', $from_name_filter);
                wp_mail($client_emails, $subject, wpautop($body), $headers);
                remove_filter('wp_mail_from', $from_email_filter);
                remove_filter('wp_mail_from_name', $from_name_filter);
            } else {
                wp_mail($client_emails, $subject, wpautop($body), $headers);
            }
        }

        private static function send_acceptance_notification_to_agency($estimate_id, ERP_OMD_Estimate_Repository $estimates, ERP_OMD_Estimate_Item_Repository $estimate_items, ERP_OMD_Estimate_Service $estimate_service, array $accept_result = [])
        {
            $estimate = (array) $estimates->find((int) $estimate_id);
            if ($estimate === []) {
                return;
            }

            $project = (array) ($accept_result['project'] ?? []);
            $project_manager_ids = [];
            $project_manager_ids[] = (int) ($project['manager_id'] ?? 0);
            foreach ((array) ($project['manager_ids'] ?? []) as $manager_id) {
                $project_manager_ids[] = (int) $manager_id;
            }
            $project_manager_ids = array_values(array_unique(array_filter($project_manager_ids)));

            $recipients = [];
            if (function_exists('get_users')) {
                $admins = get_users([
                    'role' => 'administrator',
                    'fields' => ['user_email'],
                ]);
                foreach ((array) $admins as $admin_user) {
                    $admin_email = sanitize_email((string) ($admin_user->user_email ?? ''));
                    if (is_email($admin_email)) {
                        $recipients[$admin_email] = $admin_email;
                    }
                }
            }

            $employees_repo = class_exists('ERP_OMD_Employee_Repository') ? new ERP_OMD_Employee_Repository() : null;
            foreach ($project_manager_ids as $manager_employee_id) {
                $manager_user_id = 0;
                if ($employees_repo && method_exists($employees_repo, 'find')) {
                    $manager_employee = (array) $employees_repo->find((int) $manager_employee_id);
                    $manager_user_id = (int) ($manager_employee['user_id'] ?? 0);
                }
                if ($manager_user_id <= 0) {
                    continue;
                }
                $manager_email = sanitize_email((string) get_the_author_meta('user_email', $manager_user_id));
                if (is_email($manager_email)) {
                    $recipients[$manager_email] = $manager_email;
                }
            }

            if ($recipients === []) {
                return;
            }

            $items = (array) $estimate_items->for_estimate((int) $estimate_id);
            $totals = $estimate_service->calculate_totals((array) $items);
            $estimate_name = (string) ($estimate['name'] ?? ('#' . (int) $estimate_id));
            $client_note = trim((string) ($estimate['client_decision_note'] ?? ''));
            $project_name = trim((string) ($project['name'] ?? ''));
            $client_repository = new ERP_OMD_Client_Repository();
            $client = (array) $client_repository->find((int) ($estimate['client_id'] ?? 0));
            $accept_meta = (array) get_option('erp_omd_estimate_acceptance_meta_' . (int) $estimate_id, []);

            $mail_defaults = [
                'subject' => __('[ERP OMD] Klient zaakceptował kosztorys: {estimate_name}', 'erp-omd'),
                'body' => __('Klient zaakceptował kosztorys <strong>{estimate_name}</strong>.<br><br>Projekt: <strong>{project_name}</strong><br>Kwota brutto: <strong>{final_gross}</strong><br>Uwagi klienta: {client_note}', 'erp-omd'),
            ];
            $mail_settings = wp_parse_args((array) get_option('erp_omd_estimate_internal_accept_mail_settings', []), $mail_defaults);
            $tokens = [
                '{estimate_name}' => $estimate_name,
                '{project_name}' => ($project_name !== '' ? $project_name : '—'),
                '{final_gross}' => number_format_i18n((float) ($totals['gross'] ?? 0), 2),
                '{client_note}' => ($client_note !== '' ? $client_note : '—'),
                '{estimate_id}' => (string) ((int) ($estimate['id'] ?? $estimate_id)),
                '{client_email}' => (string) ($client['email'] ?? '—'),
                '{client_name}' => (string) ($client['name'] ?? '—'),
            ];
            $subject = strtr((string) ($mail_settings['subject'] ?? $mail_defaults['subject']), $tokens);
            $body = strtr((string) ($mail_settings['body'] ?? $mail_defaults['body']), $tokens);
            $accept_meta_lines = [];
            if (! empty($accept_meta['preferred_delivery_date'])) {
                $accept_meta_lines[] = sprintf(__('Preferowany termin realizacji: %s', 'erp-omd'), (string) $accept_meta['preferred_delivery_date']);
            }
            if (! empty($accept_meta['delivery_address'])) {
                $accept_meta_lines[] = sprintf(__('Adres do dostawy: %s', 'erp-omd'), (string) $accept_meta['delivery_address']);
            }
            if (! empty($accept_meta['invoice_nip'])) {
                $accept_meta_lines[] = sprintf(__('NIP do faktury: %s', 'erp-omd'), (string) $accept_meta['invoice_nip']);
            }
            if ($accept_meta_lines !== []) {
                $body .= '<br><br><strong>' . esc_html__('Dodatkowe dane z akceptacji:', 'erp-omd') . '</strong><br>'
                    . implode('<br>', array_map('esc_html', $accept_meta_lines)) . '<br>';
            }
            $body .= self::build_summary_table_html((array) $items, $totals, true);

            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sender_name = sanitize_text_field((string) get_option('erp_omd_estimate_mail_sender_name', ''));
            $sender_email = sanitize_email((string) get_option('erp_omd_estimate_mail_sender_email', ''));
            if ($sender_email !== '' && is_email($sender_email)) {
                $headers[] = 'From: ' . ($sender_name !== '' ? $sender_name : 'WordPress') . ' <' . $sender_email . '>';
            }
            wp_mail(array_values($recipients), $subject, wpautop($body), $headers);
        }

        private static function append_accept_note_to_project_history($estimate_id, $note, array $accept_result = [])
        {
            $clean_note = trim((string) $note);
            $accept_meta = (array) get_option('erp_omd_estimate_acceptance_meta_' . (int) $estimate_id, []);
            $meta_lines = [];
            if (! empty($accept_meta['preferred_delivery_date'])) {
                $meta_lines[] = sprintf(__('Preferowany termin realizacji: %s', 'erp-omd'), (string) $accept_meta['preferred_delivery_date']);
            }
            if (! empty($accept_meta['delivery_other']) && ! empty($accept_meta['delivery_address'])) {
                $meta_lines[] = sprintf(__('Adres do dostawy: %s', 'erp-omd'), (string) $accept_meta['delivery_address']);
            }
            if (! empty($accept_meta['invoice_other_entity']) && ! empty($accept_meta['invoice_nip'])) {
                $meta_lines[] = sprintf(__('NIP do faktury: %s', 'erp-omd'), (string) $accept_meta['invoice_nip']);
            }

            if (($clean_note === '' && $meta_lines === []) || ! class_exists('ERP_OMD_Project_Note_Repository')) {
                return;
            }

            $project_id = (int) ($accept_result['project']['id'] ?? 0);
            if ($project_id <= 0 && class_exists('ERP_OMD_Project_Repository')) {
                $project_repository = new ERP_OMD_Project_Repository();
                if (method_exists($project_repository, 'find_by_estimate_id')) {
                    $project = $project_repository->find_by_estimate_id((int) $estimate_id);
                    $project_id = (int) ($project['id'] ?? 0);
                }
            }
            if ($project_id <= 0) {
                return;
            }

            $author_user_id = self::resolve_note_author_user_id();
            if ($author_user_id <= 0) {
                return;
            }

            $project_notes = new ERP_OMD_Project_Note_Repository();
            $note_parts = [];
            if ($clean_note !== '') {
                $note_parts[] = sprintf(__('Akceptacja klienta (uwaga): %s', 'erp-omd'), $clean_note);
            }
            if ($meta_lines !== []) {
                $note_parts[] = __('Dane z akceptacji kosztorysu:', 'erp-omd') . "\n- " . implode("\n- ", $meta_lines);
            }
            $project_notes->create(
                $project_id,
                implode("\n\n", $note_parts),
                $author_user_id
            );
        }

        private static function resolve_note_author_user_id()
        {
            $current_user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
            if ($current_user_id > 0) {
                return $current_user_id;
            }

            if (! function_exists('get_users')) {
                return 0;
            }

            $admins = get_users([
                'role' => 'administrator',
                'number' => 1,
                'fields' => ['ID'],
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);

            if (empty($admins) || ! isset($admins[0]->ID)) {
                return 0;
            }

            return (int) $admins[0]->ID;
        }

        private static function build_summary_table_html(array $items, array $totals, $include_comment_column = false)
        {
            if (empty($items)) {
                return '';
            }

            $html = '<br><table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
            $html .= '<thead><tr><th>' . esc_html__('Pozycja', 'erp-omd') . '</th><th>' . esc_html__('Ilość', 'erp-omd') . '</th><th>' . esc_html__('Cena', 'erp-omd') . '</th><th>' . esc_html__('Wartość', 'erp-omd') . '</th>';
            if ($include_comment_column) {
                $html .= '<th>' . esc_html__('Uwagi', 'erp-omd') . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($items as $item_row) {
                $qty = (float) ($item_row['qty'] ?? 0);
                $price = (float) ($item_row['price'] ?? 0);
                $line_total = $qty * $price;
                $html .= '<tr>';
                $html .= '<td>' . esc_html((string) ($item_row['name'] ?? '—')) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($qty, 2)) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($price, 2)) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($line_total, 2)) . '</td>';
                if ($include_comment_column) {
                    $comment = trim((string) ($item_row['comment'] ?? ''));
                    $html .= '<td>' . esc_html($comment !== '' ? $comment : '—') . '</td>';
                }
                $html .= '</tr>';
            }
            $totals_colspan = $include_comment_column ? 4 : 3;
            $html .= '</tbody><tfoot>';
            $html .= '<tr><th colspan="' . (int) $totals_colspan . '" style="text-align:right;">' . esc_html__('Suma netto', 'erp-omd') . '</th><th>' . esc_html(number_format_i18n((float) ($totals['net'] ?? 0), 2)) . '</th></tr>';
            $html .= '<tr><th colspan="' . (int) $totals_colspan . '" style="text-align:right;">' . esc_html__('Suma brutto', 'erp-omd') . '</th><th>' . esc_html(number_format_i18n((float) ($totals['gross'] ?? 0), 2)) . '</th></tr>';
            $html .= '</tfoot></table>';

            return $html;
        }

        private static function resolve_state(ERP_OMD_Estimate_Repository $estimates, $token)
        {
            $token = (string) $token;
            if ($token === '') {
                return new WP_Error('erp_omd_estimate_token_missing', __('Brak tokenu decyzji kosztorysu.', 'erp-omd'));
            }

            $state = (array) get_option('erp_omd_estimate_client_link_tokens', []);
            $now = time();
            foreach ($state as $estimate_id => $token_row) {
                if ((string) ($token_row['token'] ?? '') !== $token) {
                    continue;
                }
                $expires_at = (int) ($token_row['expires_at'] ?? 0);
                if ($expires_at > 0 && $expires_at < $now) {
                    return new WP_Error('erp_omd_estimate_token_expired', __('Link decyzji kosztorysu wygasł.', 'erp-omd'));
                }

                $estimate = $estimates->find((int) $estimate_id);
                if (! $estimate) {
                    return new WP_Error('erp_omd_estimate_not_found', __('Kosztorys nie istnieje.', 'erp-omd'));
                }
                if ((string) ($estimate['status'] ?? '') !== 'do_akceptacji') {
                    return new WP_Error('erp_omd_estimate_status_invalid', __('Kosztorys nie jest już w statusie do_akceptacji.', 'erp-omd'));
                }

                return ['estimate_id' => (int) $estimate_id, 'estimate' => $estimate, 'token_row' => $token_row];
            }

            return new WP_Error('erp_omd_estimate_token_invalid', __('Nieprawidłowy link decyzji kosztorysu.', 'erp-omd'));
        }

        private static function invalidate_token($estimate_id)
        {
            $estimate_id = (int) $estimate_id;
            if ($estimate_id <= 0) {
                return;
            }

            $state = (array) get_option('erp_omd_estimate_client_link_tokens', []);
            if (! isset($state[$estimate_id])) {
                return;
            }

            unset($state[$estimate_id]);
            update_option('erp_omd_estimate_client_link_tokens', $state, false);
        }
    }
}
