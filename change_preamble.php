<?php

/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * This page allows users to change the preamble for their department.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms/preamble_form.php');
require_once('locallib.php');

// ----- Parameters ----- //
$dept = required_param('dept', PARAM_TEXT);

// ----- Security ----- //
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Navigation ----- //
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

// ----- Stuff ---- //
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/change_preamble.php');
$PAGE->set_title(get_string('preamble', 'local_evaluations'));
$PAGE->set_heading(get_string('preamble', 'local_evaluations'));

// ----- Output ----- //
$mform = new preamble_form($dept);

//Get the data from the form if it was submitted.
if ($fromform = $mform->get_data()) {
    $record = new stdClass();
    $record->preamble = $fromform->preamble;
    $record->department = $fromform->dept;

    //Check if the department already has a preamble or not.
    if ($aRecord = $DB->get_record_select('department_preambles',
            "department = '$record->department'")) {
        //If it has one then update the record.
        $record->id = $aRecord->id;
        $DB->update_record('department_preambles', $record);
    } else {
        //Otherwise insert a new record.
        $DB->insert_record('department_preambles', $record);
    }
    //redirect to admin page.
    header('Location: ' . $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept);
} else {
    //Output the form.
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
