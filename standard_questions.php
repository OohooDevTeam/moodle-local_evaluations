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
 * This page handles the addition and removal of standard questions for each 
 * department.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('forms/standard_questions_form.php');

// ----- Parameters ---- //
$dept = required_param('dept', PARAM_TEXT);
$context = get_context_instance(CONTEXT_SYSTEM);

// ----- Security ----- //
require_login();
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
        'name' => get_string('nav_st_qe', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);
$nav = build_navigation($navlinks);

// ----- Stuff ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/standard_questions.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_st_qe', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');



// ----- Output ----- //
$standard_questions_form = new standard_questions_form($dept);


//Handle form submission.
$fromform = new stdClass();
foreach ($_REQUEST as $key => $data) {
    $fromform->$key = $data;
}

if (property_exists($fromform, 'submitbutton')) {
    process_submission($fromform);
} else if (property_exists($fromform, 'delete_question_x') || property_exists($fromform, 'swapup_question_x')
        || property_exists($fromform, 'swapdown_question_x') || property_exists($fromform, 'option_add_fields')) {
    
    //Handle delete, swapups and swapdowns.
    $q_returns[] = question_button_event('delete_question_x', 'delete', 'standard_question');
    $q_returns[] = question_button_event('swapup_question_x', 'order_swapup', 'standard_question');
    $q_returns[] = question_button_event('swapdown_question_x', 'order_swapdown', 'standard_question');

    //This will create a (#) link to the changed element on the page.
    $achor = '';
    foreach ($q_returns as $q_return) {
        //Only one should not be false.
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

