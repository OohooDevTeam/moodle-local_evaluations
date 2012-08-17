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
global $CFG, $USER;

require_once('locallib.php');
require_once('forms/response_form.php');
require_once('classes/response.php');
$eval_id = required_param('eval_id', PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluation.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('open_evaluations', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/evals.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('eval_response', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);

$nav = build_navigation($navlinks);

$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));
if ($eval_db) {
    $context = get_context_instance(CONTEXT_COURSE, $eval_db->course);
    $PAGE->set_context($context);
    $eval_name = $eval_db->name;
    require_login($eval_db->course);
} else {
    print_error(get_string('invalid_evaluation', 'local_evaluations'));
}

if(eval_check_status($eval_db)!=2){
    print_error(get_string('invalid_evaluation', 'local_evaluations'));
}


$sql = "(SELECT DISTINCT q2.evalid 
                    FROM {evaluation_questions} q2, {evaluation_response} r2 
                    WHERE r2.question_id = q2.id 
                    AND q2.evalid = $eval_db->id 
                    AND r2.user_id = $USER->id)";

$response = $DB->count_records_sql($sql);

if ($response > 0) {
    print_error(get_string('already_responded', 'local_evaluations'));
}

$PAGE->requires->css('/local/evaluations/style.css');
$PAGE->set_title(get_string('evaluation', 'local_evaluations'));
$PAGE->set_heading($eval_name);

//CREATE FORM
$response_form = new response_form($eval_id);

//DEAL WITH SUBMISSION OF FORM
if ($fromform = $response_form->get_data()) {//subbmitted
    $system_context = get_context_instance(CONTEXT_SYSTEM);
    
    //DO NOT ALLOW INSTRUCTORS OR ADMINS TO SUBMIT!
    if (!is_dept_admin($dept, $USER)
        && !has_capability('local/evaluations:instructor', $context)    ) {
    process_submission($fromform);
    }

}

//Display Form
echo $OUTPUT->header();

$response_form->display();

echo $OUTPUT->footer();

//Page Functions

function process_submission($fromform) {
    global $CFG, $USER;

    $responses = process_reponse_postdata($fromform);

    if (eval_check_status($fromform->eval_id) == 2) { //do not save if trying to submit after time has elapsed
        foreach ($responses as $response) {
            // print_object($response);
            $response = new response(0, $response->question_id, $response->response, $USER->id, $response->question_comment);
            $response->save();
        }
        // exit();
    }
    redirect($CFG->wwwroot . '/local/evaluations/evals.php');
}