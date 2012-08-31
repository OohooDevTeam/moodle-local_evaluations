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
 * This page allows department administrators to decide what reports will be used
 * for comparisons when generating the reports.
 * 
 * This will typically occur when they change the standard questions and no longer want to 
 * use the stats from the old questions.
 */
global $USER;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Parameters ----- //
$searchstring = optional_param('search', NULL, PARAM_RAW);
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page
$clear = optional_param('clear', NULL, PARAM_RAW);
$dept = required_param('dept', PARAM_TEXT);

// ----- Security ----- //
require_login();
if (!is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Naviagtion ----- //
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
        'name' => get_string('nav_cs_mx', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);
$nav = build_navigation($navlinks);

// ----- Stuff ----- //
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/coursecompare.php?dept=' . $dept);
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_cs_mx', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_cs_mx', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');

// ----- Handle Parameters ----- //
if (isset($clear)) {
    //Remove all records from course compare if clear is selected
    $evaluations = $DB->get_records_select('evaluations',
            'department = \'' . $dept . '\'');
    foreach ($evaluations as $evaluation) {
        $DB->delete_records('evaluation_compare',
                array('evalid' => $evaluation->id));
    }
} else if (!empty($_POST) && !isset($_POST['search']) && !isset($_POST['page']) && !isset($_POST['perpage'])) {
    //If search, page, and perpage are all empty then the page was probably submitted with 
    //a list of evaluations that we want to compare.
    //-----------------------------------------------
    //Delete the old ones.
    $evaluations = $DB->get_records_select('evaluations',
            'department = \'' . $dept . '\'');
    foreach ($evaluations as $evaluation) {
        $DB->delete_records('evaluation_compare',
                array('evalid' => $evaluation->id));
    }
    
    //Insert the new ones.
    foreach ($_POST as $key => $value) {

        $record = new stdClass();
        $record->evalid = $key;

        $DB->insert_record("evaluation_compare", $record);
    }
}

// ----- Output ----- //
echo $OUTPUT->header();


if (isset($searchstring)) {
    $searchterms = explode(" ", $searchstring);
    $courses = get_courses_search($searchterms, "fullname ASC", 0, 50,
            $totalcount);
} else {
    $totalcount = $DB->count_records('course');
    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/coursecompare.php', array('perpage' => $perpage));
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
    $courses = get_courses_page($categoryid = "all", $sort = "c.fullname ASC",
            $fields = "c.*", $totalcount, $perpage * $page, $perpage);
}

echo '<form action=' . $PAGE->url . ' method="post"><table width="95%" cellpadding="1" style="text-align: center;">';
foreach ($courses as $course) {
    if (is_in_department($dept, $course)) {
        $evals = $DB->get_records('evaluations',
                array('course' => $course->id, 'deleted' => 0));
        echo '<tr>';
        if (isset($courseid)) {
            echo "<td colspan=8><b>$course->fullname </b><br> $singleCourseUrl</td>";
        } else {
            echo '<td colspan=8><b>' . $course->fullname . '</b></td>';
        }
        echo '</tr>';

        table_header();

        if ($evals == null) {
            echo '<tr><td colspan=8>' . get_string('none', 'local_evaluations') . '</td></tr>';
        } else {

            foreach ($evals as $eval) {
                //print_object($eval);
                $status = eval_check_status($eval);
                $reponses = 0;
                if ($status == 1) {
                    $reponses = 0;
                } elseif ($status == 2) {
                    $reponses = get_eval_reponses_count($eval->id);
                } elseif ($status == 3) {
                    $reponses = get_eval_reponses_count($eval->id);
                }
                echo '<tr>';
                echo "<td>" . $eval->name . "</td>";
                echo '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
                echo '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
                echo '<td>' . get_eval_status($eval) . '</td>';
                echo '<td>' . $reponses . '</td>';
                //james need to make sure that these display the right things
                //used to check if the eval contains the proper questions. Or at
                //least the type of questions are the same
                echo '<td>' . $reponses . '</td>';
                //gonna use these to like pass in the id's of the evals to compare
                echo '<td> <input type="checkbox" name="' . $eval->id . '" /></td>';

                echo '</tr>';
            }
        }
        echo '<tr><td><br></td></tr>';
    }
}


echo '</tr></table>';

echo "Compare above courses: <input type='submit'/></form>";


$clearurl = $PAGE->url . '&clear=true';


echo "
    <form action='$clearurl' method='post'>
            Clear courses to compare: <input type='submit' value='Clear'>
    </form>";



echo "
    <center> 
        <form action='$PAGE->url' method='post'>
            Search: <input type='text' name ='search'/>
            <input type='submit' />
        </form>
    </center>
    ";

echo $OUTPUT->footer();

function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('status_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('response_count', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('tb_t_qok', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('tb_t_adde', 'local_evaluations') . '</th>';
    echo '</tr>';
}

?>
