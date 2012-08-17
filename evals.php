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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG, $USER;


$search = trim(optional_param('search', '', PARAM_NOTAGS));  // search string
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page

require_once('locallib.php');

//security check
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
//        if (!has_capability('local/evaluations:admin', $context)) {
//        print_error(get_string('restricted', 'local_evaluations'));
//    }

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');


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

//
//if ($dept) {
//    $navlinks[1]['link'] = $CFG->wwwroot . '/local/evaluations/evals.php';
//
//    $navlinks[] =  array(
//        'name' => get_string('open_evaluations', 'local_evaluations'),
//        'link' => '',
//        'type' => 'misc'
//    );
//}

$nav = build_navigation($navlinks);

$PAGE->set_title(get_string('open_evaluations', 'local_evaluations'));
$PAGE->set_heading(get_string('open_evaluations', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();
echo $OUTPUT->header();
//
//if (!$dept) {
//
//    $department_list = get_departments();
//    $your_administrations = $DB->get_records('department_administrators', array('userid' => $USER->id));
//
//    $your_depts = array();
//    foreach ($your_administrations as $administration) {
//        $your_depts[$administration->department] = $department_list[$administration->department];
//    }
//
//    echo '<ol>';
//    foreach ($your_depts as $code => $dept) {
//        echo '<li><a href="evals.php?dept=' . $code . '">' . $dept . '</a></li>';
//    }
//    echo '</ol>';
//
//    echo $OUTPUT->footer();
//    die();
//}


//if (is_dept_admin($dept, $USER)) {
//    $totalcount = $DB->count_records('course');
//    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/evals.php', array('perpage' => $perpage));
//    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
//    $courses = get_courses_page($categoryid = "all", $sort = "c.fullname ASC", $fields = "c.*", &$totalcount, $perpage * $page, $perpage);
//} else {
    $courses = enrol_get_my_courses();
//}



//Display Form


echo '<table width="50%" cellpadding="1" style="text-align: center;">';



foreach ($courses as $course) {
//    if (strpos($course->fullname, $dept) === 0) {
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


        $conditions = array('course' => $course->id,
            'start_time' => $current,
            'end_time' => $current,
            'complete' => $current,
        );

        $evals = $DB->get_records_sql($sql);





        echo '<tr>';
        echo '<td colspan=4><b>' . $course->fullname . '</b></td>';

        echo '</tr>';

        table_header();

        if ($evals == null) {
            echo '<tr><td colspan=4>' . get_string('none', 'local_evaluations') . '</td></tr>';
        } else {

            foreach ($evals as $eval) {

                echo '<tr>';
                //$href = $CFG->wwwroot . '/local/evaluations/response.php?eval_id='.$eval->id;
                $href = $CFG->wwwroot . '/local/evaluations/preamble.php?eval_id=' . $eval->id;

                echo "<td><a href='$href'>" . $eval->name . "</a></td>";
                echo '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
                echo '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';


                echo '</tr>';
            }
        }
        echo '<tr><td><br></td></tr>';
//    }
}


echo '</tr></table>';

echo $OUTPUT->footer();

function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '</tr>';
}

?>