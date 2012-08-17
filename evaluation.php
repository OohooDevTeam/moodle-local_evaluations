<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Simple Redirect page
 *
 * @package   local_archives
 * @copyright 2011 Dustin Durand
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Redirects to correct archives home page
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG;

require_once('locallib.php');
require_once('forms/eval_form.php');

$repeatQNum = optional_param('option_repeats', NULL, PARAM_INT);
$courseid = optional_param('cid', NULL, PARAM_INT);
$eval_id = optional_param('eval_id', 0, PARAM_INT);
$dept = required_param('dept', PARAM_TEXT);

if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

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

$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));

//Check for department permission.
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid));
} else if ($eval_id) {
    $course = $DB->get_record('course', array('id' => $eval_db->course));
}

$department_list = get_departments();
$your_administrations = $DB->get_records('department_administrators',
        array('userid' => $USER->id));

$your_depts = array();
foreach ($your_administrations as $administration) {
    $your_depts[$administration->department] = $department_list[$administration->department];
}

$course_context = get_context_instance(CONTEXT_COURSE, $course->id);
$is_instructor = has_capability('local/evaluations:instructor', $course_context);

//If the key does not exist then he had no access to this department.
if (!array_key_exists(substr($course->fullname, 0, 4), $your_depts) || $is_instructor) {
    print_error(get_string('restricted', 'local_evaluations'));
}


$eval_name = get_string('new_evaluation', 'local_evaluations');


$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
require_login();


$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_title(get_string('evaluation', 'local_evaluations'));
$PAGE->set_heading($eval_name);

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
} elseif ($eval_form->no_submit_button_pressed()) {//fake-submit
    //delete button pressed
    $q_returns[] = question_button_event('delete_question_x', 'delete',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup',
            'question', $eval_id);
    $q_returns[] = question_button_event('swapdown_question_x',
            'order_swapdown', 'question', $eval_id);


    $achor = '';
    foreach ($q_returns as $q_return) {
        if ($q_return != false) {
            $achor = $q_return;
            break;
        }
    }
    $additional = '';


    if (isset($_REQUEST['option_add_fields'])) {
//        if ($achor == '') {
        $additional = '&option_repeats=' . ($_REQUEST['option_repeats'] + 1);
//        }
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

//Page Functions

function process_submission($fromform) {

    global $CFG, $course, $dept;

    $questions = process_question_postdata($fromform);
    $start = $fromform->eval_time_start;
    $end = $fromform->eval_time_end;

    $evaluation = new evaluation($dept, $fromform->eval_id, $questions, $fromform->eval_course_id, $start,
                    $end, $fromform->eval_name, $fromform->student_email_toogle, $fromform->eval_complete, $fromform->eval_type, $db_load = false);
    $evaluation->save();

    if (trim($fromform->eval_type) == 'invig') { //face to face with invig
        redirect($CFG->wwwroot . '/local/evaluations/invigilator.php?eval_id=' . $evaluation->get_id());
    }

    redirect($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . substr($course->fullname,
                    0, 4));
}
