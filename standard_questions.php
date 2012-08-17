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
require_once('forms/standard_questions_form.php');

$dept = required_param('dept', PARAM_TEXT);

//security check
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}


$PAGE->set_url($CFG->wwwroot . '/local/evaluations/standard_questions.php');

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
        'name' => get_string('nav_st_qe', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);

$nav = build_navigation($navlinks);


$PAGE->set_title(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();



//CREATE FORM
$standard_questions_form = new standard_questions_form($dept);

//DEAL WITH SUBMISSION OF FORM
$fromform = new stdClass();
foreach ($_REQUEST as $key => $data) {
    $fromform->$key = $data;
}

if (property_exists($fromform, 'submitbutton')) {
    process_submission($fromform);
} else if (property_exists($fromform, 'delete_question_x') || property_exists($fromform, 'swapup_question_x')
        || property_exists($fromform, 'swapdown_question_x') || property_exists($fromform, 'option_add_fields')) {
//print_object($_REQUEST);exit();
    $q_returns[] = question_button_event('delete_question_x', 'delete', 'standard_question');
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup', 'standard_question');
    $q_returns[] = question_button_event('swapdown_question_x', 'order_swapdown', 'standard_question');

    $achor = '';
    foreach ($q_returns as $q_return) {
        if ($q_return != false) {
            $achor = $q_return;
            break;
        }
    }

//Overwrite repeat options on redirect
//Hackish - should change
    $additional = '';
    if (isset($_REQUEST['option_add_fields'])) {
        if ($achor == '') {
            $additional = '&option_repeats=' . ($_REQUEST['option_repeats'] + 1);
        }
    }
    redirect($CFG->wwwroot . '/local/evaluations/standard_questions.php' . '?dept=' . $dept . $additional . $achor);
} else
if (isset($_REQUEST['cancel'])) {
    redirect($CFG->wwwroot . '/local/evaluations/standard_questions.php' . '?dept=' . $dept . $additional . $achor);
}

//Display Form
echo $OUTPUT->header();

$standard_questions_form->display();

echo $OUTPUT->footer();

//Page Functions

function process_submission($fromform) {
    global $CFG, $dept;

    $questions = process_question_postdata($fromform);



    $standard_question_set = new standard_question_set($dept, $questions);
    $standard_question_set->save();


    redirect($CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept);
}

