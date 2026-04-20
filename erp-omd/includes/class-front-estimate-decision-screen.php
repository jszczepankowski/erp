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
                            $estimates->save_client_decision_note($estimate_id, $note);
                            self::send_thank_you_mail($estimate_id, $estimates, $estimate_items, $estimate_service);
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
            $client_email = sanitize_email((string) ($client['email'] ?? ''));
            if (! is_email($client_email)) {
                return;
            }

            $items = $estimate_items->for_estimate((int) $estimate_id);
            $totals = $estimate_service->calculate_totals($items);
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
            $body .= self::build_summary_table_html($items, $totals);
            wp_mail($client_email, $subject, wpautop($body), ['Content-Type: text/html; charset=UTF-8']);
        }

        private static function build_summary_table_html(array $items, array $totals)
        {
            if (empty($items)) {
                return '';
            }

            $html = '<br><table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
            $html .= '<thead><tr><th>' . esc_html__('Pozycja', 'erp-omd') . '</th><th>' . esc_html__('Ilość', 'erp-omd') . '</th><th>' . esc_html__('Cena', 'erp-omd') . '</th><th>' . esc_html__('Wartość', 'erp-omd') . '</th></tr></thead><tbody>';
            foreach ($items as $item_row) {
                $qty = (float) ($item_row['qty'] ?? 0);
                $price = (float) ($item_row['price'] ?? 0);
                $line_total = $qty * $price;
                $html .= '<tr>';
                $html .= '<td>' . esc_html((string) ($item_row['name'] ?? '—')) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($qty, 2)) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($price, 2)) . '</td>';
                $html .= '<td>' . esc_html(number_format_i18n($line_total, 2)) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody><tfoot>';
            $html .= '<tr><th colspan="3" style="text-align:right;">' . esc_html__('Suma netto', 'erp-omd') . '</th><th>' . esc_html(number_format_i18n((float) ($totals['net'] ?? 0), 2)) . '</th></tr>';
            $html .= '<tr><th colspan="3" style="text-align:right;">' . esc_html__('Suma brutto', 'erp-omd') . '</th><th>' . esc_html(number_format_i18n((float) ($totals['gross'] ?? 0), 2)) . '</th></tr>';
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
