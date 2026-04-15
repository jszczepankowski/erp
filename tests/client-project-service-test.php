<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('is_email')) {
    function is_email($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (! function_exists('get_attached_file')) {
    function get_attached_file($attachment_id)
    {
        return $GLOBALS['erp_omd_attachment_file_map'][(int) $attachment_id] ?? '';
    }
}

if (! function_exists('get_post_mime_type')) {
    function get_post_mime_type($attachment_id)
    {
        return $GLOBALS['erp_omd_attachment_mime_map'][(int) $attachment_id] ?? '';
    }
}

if (! function_exists('wp_check_filetype_and_ext')) {
    function wp_check_filetype_and_ext($path, $filename)
    {
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $mime = $extension === 'pdf' ? 'application/pdf' : 'text/plain';

        foreach ((array) ($GLOBALS['erp_omd_attachment_file_map'] ?? []) as $attachment_id => $mapped_path) {
            if ((string) $mapped_path === (string) $path) {
                $mime = (string) ($GLOBALS['erp_omd_attachment_mime_map'][(int) $attachment_id] ?? $mime);
                if ($mime === 'application/pdf') {
                    $extension = 'pdf';
                } elseif ($extension === '') {
                    $extension = 'txt';
                }
                break;
            }
        }

        return [
            'ext' => $extension,
            'type' => $mime,
            'proper_filename' => $filename,
        ];
    }
}

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $GLOBALS['erp_omd_test_options'][$key] ?? $default;
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;

        public function __construct(array $clients = [])
        {
            $this->clients = $clients;
        }

        public function find($id)
        {
            return $this->clients[(int) $id] ?? null;
        }

        public function nip_exists($nip, $exclude_id = null)
        {
            foreach ($this->clients as $id => $client) {
                if ((string) ($client['nip'] ?? '') === (string) $nip && (int) $id !== (int) $exclude_id) {
                    return true;
                }
            }

            return false;
        }
    }
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository
    {
        private $employees;

        public function __construct(array $employees = [])
        {
            $this->employees = $employees;
        }

        public function find($id)
        {
            return $this->employees[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Role_Repository')) {
    class ERP_OMD_Role_Repository
    {
        private $roles;

        public function __construct(array $roles = [])
        {
            $this->roles = $roles;
        }

        public function find($id)
        {
            return $this->roles[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
    }
}

if (! class_exists('ERP_OMD_Attachment_Repository')) {
    class ERP_OMD_Attachment_Repository
    {
        private $attachments_by_entity;

        public function __construct(array $attachments_by_entity = [])
        {
            $this->attachments_by_entity = $attachments_by_entity;
        }

        public function for_entity($entity_type, $entity_id)
        {
            return $this->attachments_by_entity[$entity_type . ':' . (int) $entity_id] ?? [];
        }
    }
}

if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository
    {
        private $counts;

        public function __construct(array $counts = [])
        {
            $this->counts = $counts;
        }

        public function count_for_project_by_statuses($project_id, array $statuses)
        {
            return $this->counts[(int) $project_id] ?? 0;
        }
    }
}

if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service
    {
        private $alerts;

        public function __construct(array $alerts = [])
        {
            $this->alerts = $alerts;
        }

        public function alerts_for_entity($entity_type, $entity_id)
        {
            return $this->alerts[$entity_type . ':' . (int) $entity_id] ?? [];
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-client-project-service.php';
require_once __DIR__ . '/../erp-omd/includes/services/class-project-attachment-service.php';

final class ClientProjectServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $valid_pdf_path = tempnam(sys_get_temp_dir(), 'erp_omd_pdf_valid_');
        $invalid_mime_path = tempnam(sys_get_temp_dir(), 'erp_omd_pdf_invalid_mime_');
        $broken_pdf_path = tempnam(sys_get_temp_dir(), 'erp_omd_pdf_broken_');
        $oversized_pdf_path = tempnam(sys_get_temp_dir(), 'erp_omd_pdf_big_');
        file_put_contents($valid_pdf_path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        file_put_contents($invalid_mime_path, "NOT_A_PDF\ncontent");
        file_put_contents($broken_pdf_path, "%PDF-1.4\nbroken-without-eof");
        file_put_contents($oversized_pdf_path, "%PDF-1.4\n" . str_repeat('A', 6 * 1024 * 1024) . "\n%%EOF");

        $GLOBALS['erp_omd_attachment_file_map'] = [
            1001 => $valid_pdf_path,
            1002 => $invalid_mime_path,
            1003 => $broken_pdf_path,
            1004 => $oversized_pdf_path,
        ];
        $GLOBALS['erp_omd_attachment_mime_map'] = [
            1001 => 'application/pdf',
            1002 => 'text/plain',
            1003 => 'application/pdf',
            1004 => 'application/pdf',
        ];
        $GLOBALS['erp_omd_test_options'] = [
            'erp_omd_ksef_sales_inbox' => [
                ['id' => 1, 'project_id' => 13, 'is_final' => 1],
                ['id' => 2, 'project_id' => 13, 'is_final' => 0],
            ],
        ];

        $attachment_service = new ERP_OMD_Project_Attachment_Service(
            new ERP_OMD_Attachment_Repository([
                'project:11' => [],
                'project:12' => [
                    ['attachment_id' => 1002],
                ],
                'project:13' => [
                    ['attachment_id' => 1001],
                ],
            ])
        );

        $service = new ERP_OMD_Client_Project_Service(
            new ERP_OMD_Client_Repository([
                1 => ['id' => 1, 'nip' => '1234567890'],
            ]),
            new ERP_OMD_Employee_Repository([
                5 => ['id' => 5],
                6 => ['id' => 6],
            ]),
            new ERP_OMD_Role_Repository([
                8 => ['id' => 8],
            ]),
            new ERP_OMD_Project_Repository(),
            new ERP_OMD_Time_Entry_Repository([
                10 => 2,
            ]),
            new ERP_OMD_Alert_Service([
                'project:10' => [
                    ['severity' => 'error', 'code' => 'project_budget_exceeded'],
                ],
            ]),
            $attachment_service
        );

        $preparedClient = $service->prepare_client([
            'name' => ' ACME ',
            'nip' => '123-456-32-18',
            'phone' => '600 700 800',
            'contact_person_phone' => '48 111 222 333',
            'postal_code' => '00950',
            'country' => 'pl',
        ]);
        $this->assertSame('1234563218', $preparedClient['nip'], 'Client NIP should be normalized to digits only.');
        $this->assertSame('+600700800', $preparedClient['phone'], 'Client phone should be normalized with country-style prefix.');
        $this->assertSame('00-950', $preparedClient['postal_code'], 'Postal code should be normalized to 00-000 format.');
        $this->assertSame('PL', $preparedClient['country'], 'Country should be normalized to ISO alpha-2 code.');

        $clientErrors = $service->validate_client([
            'name' => 'Nowy klient',
            'nip' => '1234',
            'email' => 'bad-email',
            'contact_person_email' => 'bad-email',
            'phone' => 'abc',
            'contact_person_phone' => '123',
            'postal_code' => '123',
            'status' => 'active',
            'account_manager_id' => 999,
        ]);
        $this->assertSame(6, count($clientErrors), 'Client validation should report normalized contact and manager issues.');

        $fixedPriceErrors = $service->validate_project([
            'client_id' => 1,
            'name' => 'Projekt A',
            'billing_type' => 'fixed_price',
            'budget' => 0,
            'retainer_monthly_fee' => 100,
            'status' => 'do_rozpoczecia',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'manager_id' => 5,
            'estimate_id' => 0,
            'brief' => '',
            'alert_margin_threshold' => '',
        ]);
        $this->assertTrue(in_array('Projekt fixed_price wymaga dodatniego budżetu.', $fixedPriceErrors, true), 'Fixed price projects should require a positive budget.');
        $this->assertTrue(in_array('Projekt fixed_price nie może mieć opłaty retainer.', $fixedPriceErrors, true), 'Fixed price projects should reject retainer fees.');

        $preparedProject = $service->prepare_project([
            'client_id' => 1,
            'name' => 'Projekt z zespołem managerskim',
            'billing_type' => 'time_material',
            'budget' => 0,
            'retainer_monthly_fee' => 0,
            'status' => 'do_rozpoczecia',
            'manager_id' => 5,
            'manager_ids' => [6],
        ]);
        $this->assertSame([5, 6], $preparedProject['manager_ids'], 'Primary manager should be merged into manager_ids list.');

        $multiManagerErrors = $service->validate_project([
            'client_id' => 1,
            'name' => 'Projekt C',
            'billing_type' => 'time_material',
            'budget' => 0,
            'retainer_monthly_fee' => 0,
            'status' => 'do_rozpoczecia',
            'manager_id' => 5,
            'manager_ids' => [5, 999],
            'estimate_id' => 0,
            'brief' => '',
            'alert_margin_threshold' => '',
        ]);
        $this->assertTrue(in_array('Każdy manager projektu musi wskazywać istniejącego pracownika.', $multiManagerErrors, true), 'Project validation should reject unknown additional managers.');

        $retainerErrors = $service->validate_project([
            'client_id' => 1,
            'name' => 'Projekt B',
            'billing_type' => 'retainer',
            'budget' => 100,
            'retainer_monthly_fee' => 0,
            'status' => 'do_rozpoczecia',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'manager_id' => 5,
            'estimate_id' => 0,
            'brief' => '',
            'alert_margin_threshold' => '',
        ]);
        $this->assertTrue(in_array('Projekt retainer wymaga dodatniej opłaty miesięcznej.', $retainerErrors, true), 'Retainer projects should require a monthly fee.');
        $this->assertTrue(in_array('Projekt retainer nie powinien mieć budżetu fixed price — ustaw 0.', $retainerErrors, true), 'Retainer projects should reject fixed-price budget values.');

        $lifecycleErrors = $service->validate_project(
            [
                'client_id' => 1,
                'name' => 'Projekt C',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'do_faktury',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'manager_id' => 5,
                'estimate_id' => 0,
                'brief' => '',
                'alert_margin_threshold' => 12,
            ],
            [
                'id' => 10,
                'client_id' => 1,
                'name' => 'Projekt C',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'w_realizacji',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'manager_id' => 5,
                'estimate_id' => 0,
                'brief' => '',
                'alert_margin_threshold' => null,
            ]
        );
        $this->assertTrue(in_array('Projekt nie może przejść do do_faktury, jeśli ma niezatwierdzone wpisy czasu.', $lifecycleErrors, true), 'Project should not enter do_faktury with pending time entries.');
        $this->assertTrue(in_array('Projekt nie może przejść do do_faktury, jeśli ma aktywne alerty krytyczne.', $lifecycleErrors, true), 'Project should not enter do_faktury with critical alerts.');

        $missingPdfErrors = $service->validate_project(
            [
                'client_id' => 1,
                'name' => 'Projekt bez faktury',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'zakonczony',
                'manager_id' => 5,
            ],
            [
                'id' => 11,
                'client_id' => 1,
                'name' => 'Projekt bez faktury',
                'billing_type' => 'time_material',
                'budget' => 0,
                'retainer_monthly_fee' => 0,
                'status' => 'do_faktury',
                'manager_id' => 5,
            ]
        );
        $this->assertTrue(in_array('Projekt nie może przejść do zakończony bez co najmniej jednej końcowej faktury PDF.', $missingPdfErrors, true), 'Project should not close without a final PDF invoice.');
        $this->assertTrue(in_array('Projekt nie może przejść do zakończony bez co najmniej jednej końcowej faktury sprzedażowej.', $missingPdfErrors, true), 'Project should not close without a final sales invoice attached to the project.');

        $invalidMimeErrors = $service->validate_project(
            ['client_id' => 1, 'name' => 'Projekt z błędnym MIME', 'billing_type' => 'time_material', 'budget' => 0, 'retainer_monthly_fee' => 0, 'status' => 'zakonczony', 'manager_id' => 5],
            ['id' => 12, 'client_id' => 1, 'name' => 'Projekt z błędnym MIME', 'billing_type' => 'time_material', 'budget' => 0, 'retainer_monthly_fee' => 0, 'status' => 'do_faktury', 'manager_id' => 5]
        );
        $this->assertTrue(in_array('Projekt nie może przejść do zakończony — brak poprawnej końcowej faktury PDF (MIME application/pdf, maks. 5 MB, poprawna integralność pliku).', $invalidMimeErrors, true), 'Project should reject closing when only non-PDF invoice attachments exist.');

        $tooBigErrors = $attachment_service->validate_pdf_attachment(1004);
        $this->assertTrue(
            in_array('Plik faktury PDF przekracza maksymalny rozmiar 5 MB.', $tooBigErrors, true)
                || in_array('Plik faktury PDF ma niepoprawny rozmiar.', $tooBigErrors, true),
            'PDF validation should reject files bigger than 5 MB.'
        );

        $validCloseErrors = $service->validate_project(
            ['client_id' => 1, 'name' => 'Projekt z poprawną fakturą', 'billing_type' => 'time_material', 'budget' => 0, 'retainer_monthly_fee' => 0, 'status' => 'zakonczony', 'manager_id' => 5],
            ['id' => 13, 'client_id' => 1, 'name' => 'Projekt z poprawną fakturą', 'billing_type' => 'time_material', 'budget' => 0, 'retainer_monthly_fee' => 0, 'status' => 'do_faktury', 'manager_id' => 5]
        );
        $this->assertSame(false, in_array('Projekt nie może przejść do zakończony bez co najmniej jednej końcowej faktury PDF.', $validCloseErrors, true), 'Project with valid PDF should be allowed to close.');

        echo "Assertions: {$this->assertions}\n";
        echo "Client project service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    private function assertTrue($condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }
}

(new ClientProjectServiceTestRunner())->run();
