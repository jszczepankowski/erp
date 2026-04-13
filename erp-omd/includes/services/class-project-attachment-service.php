<?php

class ERP_OMD_Project_Attachment_Service
{
    const MAX_PDF_BYTES = 5242880; // 5 MB

    private $attachments;

    public function __construct(ERP_OMD_Attachment_Repository $attachments)
    {
        $this->attachments = $attachments;
    }

    public function has_valid_final_invoice_pdf($project_id, &$errors = [])
    {
        $errors = [];
        $valid_count = 0;
        $relations = $this->attachments->for_entity('project', (int) $project_id);

        foreach ($relations as $relation) {
            $validation_errors = $this->validate_pdf_attachment((int) ($relation['attachment_id'] ?? 0));
            if ($validation_errors === []) {
                $valid_count++;
            }
        }

        if ($valid_count > 0) {
            return true;
        }

        if ($relations === []) {
            $errors[] = __('Projekt nie może przejść do zakończony bez co najmniej jednej końcowej faktury PDF.', 'erp-omd');
            return false;
        }

        $errors[] = __('Projekt nie może przejść do zakończony — brak poprawnej końcowej faktury PDF (MIME application/pdf, maks. 5 MB, poprawna integralność pliku).', 'erp-omd');
        return false;
    }

    public function validate_pdf_attachment($attachment_id)
    {
        $attachment_id = (int) $attachment_id;
        $errors = [];

        if ($attachment_id <= 0) {
            return [__('Nie znaleziono załącznika PDF.', 'erp-omd')];
        }

        $path = function_exists('get_attached_file') ? (string) get_attached_file($attachment_id) : '';
        if ($path === '' || ! is_readable($path)) {
            return [__('Nie można odczytać pliku faktury PDF z biblioteki mediów.', 'erp-omd')];
        }

        $size = @filesize($path);
        if (! is_int($size) || $size <= 0) {
            $errors[] = __('Plik faktury PDF ma niepoprawny rozmiar.', 'erp-omd');
        } elseif ($size > self::MAX_PDF_BYTES) {
            $errors[] = __('Plik faktury PDF przekracza maksymalny rozmiar 5 MB.', 'erp-omd');
        }

        if (! $this->is_pdf_mime($attachment_id, $path)) {
            $errors[] = __('Plik faktury końcowej musi mieć MIME application/pdf.', 'erp-omd');
        }

        if (! $this->has_pdf_integrity($path)) {
            $errors[] = __('Plik faktury PDF jest uszkodzony lub niekompletny (integralność PDF).', 'erp-omd');
        }

        return array_values(array_unique($errors));
    }

    private function is_pdf_mime($attachment_id, $path)
    {
        $mimes = [];
        if (function_exists('get_post_mime_type')) {
            $mimes[] = strtolower((string) get_post_mime_type($attachment_id));
        }

        if (function_exists('wp_check_filetype_and_ext')) {
            $checked = (array) wp_check_filetype_and_ext($path, basename($path));
            $mimes[] = strtolower((string) ($checked['type'] ?? ''));
        }

        $mimes = array_values(array_filter(array_unique($mimes)));
        return in_array('application/pdf', $mimes, true);
    }

    private function has_pdf_integrity($path)
    {
        $head = @file_get_contents($path, false, null, 0, 5);
        if ($head !== '%PDF-') {
            return false;
        }

        $size = @filesize($path);
        if (! is_int($size) || $size <= 0) {
            return false;
        }

        $tail_length = min(2048, $size);
        $tail = @file_get_contents($path, false, null, $size - $tail_length, $tail_length);
        if (! is_string($tail) || $tail === '') {
            return false;
        }

        return strpos($tail, '%%EOF') !== false;
    }
}
