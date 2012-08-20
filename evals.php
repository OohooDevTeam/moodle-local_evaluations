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
 * This page shows users what evaluations they have available to fill out.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Security -----//
require_login();

// ----- Navigation ----- //
$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('open_evaluations', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);
$nav = build_navigation($navlinks);

// ----- Stuff ----- //
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');
$PAGE->set_title(get_string('open_evaluations', 'local_evaluations'));
$PAGE->set_heading(get_string('open_evaluations', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');

// ----- Output ----- //
echo $OUTPUT->header();


echo '<table width="50%" cellpadding="1" style="text-align: center;">';

$courses = enrol_get_my_courses();

foreach ($courses as $course) {
    $current = time();


    //Select all evals that are in progress
    //then strip all evals that already have responses from that user
    $sql = "SELECT * 
        FROM {evaluations} e 
        WHERE e.course = $course->id 
                AND e.start_time <= $current 
                AND e.end_time > $current AND e.complete <> 1 AND e.deleted <> 1
                AND e.id NOT IN 
                
                    (SELECT q2.evalid 
                    FROM {evaluation_questions} q2, {evaluation_response} r2 
                    WHERE r2.question_id = q2.id 
                    AND q2.evalid = e.id 
                    AND r2.user_id = $USER->id)";


    //Print the course name
    echo '<tr><td colspan=4><b>' . $course->fullname . '</b></td></tr>';

    //Output the table header.
    table_header();

    //Get a list of all evaluations.
    $evals = $DB->get_records_sql($sql);
    if ($evals == null || count($evals) == 0) {
        //Warn the users that there are no evaluations for this course to be taken.
        echo '<tr><td colspan=4>' . get_string('none', 'local_evaluations') . '</td></tr>';
    } else {
        foreach ($evals as $eval) {
            $href = $CFG->wwwroot . '/local/evaluations/preamble.php?eval_id=' . $eval->id; //Link to evaluation.
            //Print the evaluation info along with evaluation link.
            echo '<tr>';
            echo "  <td><a href='$href'>" . $eval->name . "</a></td>";
            echo '  <td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
            echo '  <td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
            echo '</tr>';
        }
    }
    echo '<tr><td><br></td></tr>';
}

echo '</tr></table>';

echo $OUTPUT->footer();

/**
 * Print the header for the evaluation table.
 */
function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '</tr>';
}

?>