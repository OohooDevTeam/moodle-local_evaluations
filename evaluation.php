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
 * This page handles the creation of new evaluations.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('forms/eval_form.php');
// ----- Parameters ----- //
$courseid = optional_param('cid', NULL, PARAM_INT);
$eval_id = optional_param('eval_id', 0, PARAM_INT);
$dept = required_param('dept', PARAM_TEXT);
$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));
$context = get_context_instance(CONTEXT_SYSTEM);

//Check for department permission.
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid));
} else if ($eval_id) {
    $course = $DB->get_record('course', array('id' => $eval_db->course));
}

$department_list = get_departments();

$course_context = get_context_instance(CONTEXT_COURSE, $course->id);
$is_instructor = has_capability('local/evaluations:instructor', $course_context);

// ----- Security ----- //
require_login();

if (!is_dept_admin($dept, $USER) || $is_instructor) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Navigation ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluation.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('nav_ev_course', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/evaluations.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('evaluation', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);
$nav = build_navigation($navlinks);

// ----- Stuff ----- //
$eval_name = get_string('new_evaluation', 'local_evaluations');
$PAGE->set_context($context);
$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_title(get_string('evaluation', 'local_evaluations'));
$PAGE->set_heading($eval_name);

// ----- Output ----- //
//CREATE FORM
if (isset($courseid))
    $eval_form = new eval_form($dept, $eval_id, $courseid);
else {
    $eval_form = new eval_form($dept, $eval_id);
}

$PAGE->requires->js('/local/evaluations/evaluation.js');

//DEAL WITH SUBMISSION OF FORM
if ($fromform = $eval_form->get_data()) {//subbmitted
    process_submission($fromform);
} elseif ($eval_form->no_submit_button_pressed()) {//Occurs with delete swapup and swapdown
    //Handle delete, swapups and swapdowns.
    $q_returns[] = question_button_event('delete_question_x', 'delete',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapdown_question_x',
            'order_swapdown', 'question', $eval_id);


    //This will create a (#) link to the changed element on the page.
    $achor = '';
    foreach ($q_returns as $q_return) {
        //Only one should not be false.
        if ($q_return != false) {
            $achor = $q_return;
            break;
        }
    }

    $additional = '';
    if (isset($_REQUEST['option_add_fields'])) {
        $additional = '&option_repeats=' . ($_REQUEST['option_repeats'] + 1);
    }

    if (isset($_REQUEST['cid'])) {
        redirect($CFG->wwwroot . '/local/evaluations/evaluation.php?dept=' . $dept . '&cid=' . $courseid . '&eval_id=' . $eval_id . $additional . $achor);
    } else {
        redirect($CFG->wwwroot . '/local/evaluations/evaluation.php?dept=' . $dept . '&eval_id=' . $eval_id . $additional . $achor);
    }
}

//Display Form
echo $OUTPUT->header();

$eval_form->display();

echo $OUTPUT->footer();

// ----- Functions ----- //
function process_submission($fromform) {
    global $CFG, $dept;

    //get the question objects from the form data.
    $questions = process_question_postdata($fromform);
    $start = $fromform->eval_time_start;
    $end = $fromform->eval_time_end;

    //Create a new evaluation with the posted data.
    $evaluation = new evaluation($dept, $fromform->eval_id, $questions, $fromform->eval_course_id, $start,
                    $end, $fromform->eval_name, $fromform->student_email_toogle, $fromform->eval_complete, $db_load = false);

    //Save the evaluation.
    $evaluation->save();

    //Redirect to list of evaluations for this department.
    redirect($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept);
}
