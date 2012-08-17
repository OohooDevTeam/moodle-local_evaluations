<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms/preamble_form.php');
require_once('locallib.php');


$dept = optional_param('dept', false, PARAM_TEXT);
//security
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/change_preamble.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('dept_selection', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/admin.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('nav_admin', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept,
        'type' => 'misc'
    ),
    array(
        'name' => get_string('preamble', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);

$nav = build_navigation($navlinks);

$PAGE->set_title(get_string('preamble', 'local_evaluations'));
$PAGE->set_heading(get_string('preamble', 'local_evaluations'));

$mform = new preamble_form($dept);

if ($fromform = $mform->get_data()) {
    $record = new stdClass();
    $record->preamble = $fromform->preamble;
    $record->department = $fromform->dept;

    if ($aRecord = $DB->get_record_select('department_preambles',
            "department = '$record->department'")) {
        $record->id = $aRecord->id;
        $DB->update_record('department_preambles', $record);
    } else {
        $DB->insert_record('department_preambles', $record);
    }
    header('Location: ' . $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept);

}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
?>
