<?php

$current_user = wp_get_current_user();
$current_employee = $this->employees->find_by_user_id($current_user->ID);
$can_select_any_employee = current_user_can('administrator') || current_user_can('erp_omd_approve_time');
$can_edit_any_entry = current_user_can('administrator');
$can_delete_entries = $this->time_entry_service->can_delete_entry($current_user);
$filters = [
    'employee_id' => isset($_GET['employee_id']) ? $_GET['employee_id'] : '',
    'client_id' => isset($_GET['client_id']) ? $_GET['client_id'] : '',
    'project_id' => isset($_GET['project_id']) ? $_GET['project_id'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'entry_date' => isset($_GET['entry_date']) ? $_GET['entry_date'] : '',
    'month' => sanitize_text_field(wp_unslash(isset($_GET['month']) ? $_GET['month'] : '')),
];
$pagination = [
    'page_num' => max(1, (int) (isset($_GET['page_num']) ? $_GET['page_num'] : 1)),
    'per_page' => (int) (isset($_GET['per_page']) ? $_GET['per_page'] : 100),
];
if (! in_array($pagination['per_page'], [25, 50, 100, 200], true)) {
    $pagination['per_page'] = 100;
}
if (! $can_select_any_employee && $current_employee) {
    $filters['employee_id'] = (string) $current_employee['id'];
}

$entry = ! empty($_GET['id']) ? $this->time_entries->find((int) $_GET['id']) : null;
$can_edit_selected_entry = $entry ? $this->time_entry_service->can_edit_entry($entry, $current_user) : true;
if ($entry && ! $can_edit_selected_entry) {
    $entry = null;
}

$employees_for_select = $this->employees->all();
$projects_for_time = $this->projects->all();
$clients_for_time = $this->clients->all();
$roles = $this->roles->all();
$query_filters = array_filter($filters, [$this, 'is_query_filter']);
$total_time_entries = $this->time_entries->count_filtered($query_filters);
$time_entries = $this->time_entries->find_paged(
    $query_filters,
    $pagination['per_page'],
    ($pagination['page_num'] - 1) * $pagination['per_page']
);
$time_entries = $this->time_entry_service->filter_visible_entries($time_entries, $current_user);
$pagination['total_items'] = $total_time_entries;
$pagination['total_pages'] = max(1, (int) ceil($total_time_entries / max(1, $pagination['per_page'])));
if ($pagination['page_num'] > $pagination['total_pages']) {
    $pagination['page_num'] = $pagination['total_pages'];
}
$selected_employee_id = isset($entry['employee_id'])
    ? $entry['employee_id']
    : (isset($current_employee['id']) ? $current_employee['id'] : 0);
$selected_time_client_id = 0;
if ($entry) {
    foreach ($projects_for_time as $project_row) {
        if ((int) ($project_row['id'] ?? 0) === (int) ($entry['project_id'] ?? 0)) {
            $selected_time_client_id = (int) ($project_row['client_id'] ?? 0);
            break;
        }
    }
} elseif (! empty($filters['client_id'])) {
    $selected_time_client_id = (int) $filters['client_id'];
}
$can_set_status = current_user_can('administrator') || current_user_can('erp_omd_approve_time');
include ERP_OMD_PATH . 'templates/admin/time-entries.php';
