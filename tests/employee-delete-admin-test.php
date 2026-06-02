<?php

declare(strict_types=1);

$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$repository = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-employee-repository.php');
$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/employees.php');

if ($admin === '' || $repository === '' || $template === '') {
    throw new RuntimeException('Unable to load one of files for employee delete test.');
}

$assertions = 0;
$fragments = [
    [$admin, "case 'delete_employee'", 'Admin runtime should dispatch employee delete action.'],
    [$admin, 'function handle_employee_delete(', 'Admin runtime should expose employee delete handler.'],
    [$admin, "check_admin_referer('erp_omd_delete_employee')", 'Employee delete handler should verify nonce.'],
    [$admin, "require_capability('erp_omd_manage_employees')", 'Employee delete handler should require employee management capability.'],
    [$admin, 'get_current_user_id()', 'Employee delete handler should protect the current employee profile from self-deletion.'],
    [$admin, '$this->employees->delete($id)', 'Employee delete handler should call employee repository delete.'],
    [$admin, 'function clear_employee_front_access(', 'Employee delete handler should clear FRONT access for linked WP user.'],
    [$admin, "'erp_omd_worker'", 'Employee delete should remove worker FRONT role.'],
    [$admin, "'erp_omd_manager'", 'Employee delete should remove manager FRONT role.'],
    [$repository, 'function delete(', 'Employee repository should expose hard delete method.'],
    [$repository, '$wpdb->delete($this->pivot_table_name()', 'Employee repository should remove role pivot rows before deleting employee.'],
    [$repository, '$wpdb->delete($this->table_name()', 'Employee repository should delete employee row.'],
    [$template, "wp_nonce_field('erp_omd_delete_employee')", 'Employees template should render delete nonce.'],
    [$template, 'name="erp_omd_action" value="delete_employee"', 'Employees template should post employee delete action.'],
    [$template, "esc_html_e('Usuń', 'erp-omd')", 'Employees template should render delete button.'],
    [$template, 'button-link-delete', 'Employees template should use destructive button styling.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "Employee delete admin test passed.\n";
