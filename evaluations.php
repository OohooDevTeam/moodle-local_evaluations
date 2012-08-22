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
 * This page allows department administrators to build new evaluations for their
 * departments.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Parameters ----- //
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page

$searchstring = optional_param('search', false, PARAM_RAW);
$courseid = optional_param('cid', false, PARAM_INT);
$dept = optional_param('dept', false, PARAM_TEXT);


// ----- Navigation ----- //
$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('dept_selection', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    )
);
//If a department was selected the create a link the the department selection page
if ($dept) {
    $navlinks[1]['link'] = $CFG->wwwroot . '/local/evaluations/evaluations.php';

    $navlinks[] = array(
        'name' => get_string('nav_ev_course', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    );
}
$nav = build_navigation($navlinks);

// ----- Stuff ----- //
//If dept isn't specified here it will still redirect properly to the dept selection
//page.
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept);

$PAGE->requires->css('/local/evaluations/style.css');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav_ev_course', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_course', 'local_evaluations'));

// ----- Security ------ //
require_login();

//Give a list of departments to choose from if they have not already specified one.
if (!$dept) {
    echo $OUTPUT->header();

    //Get a list of all departments that the user is an administrator for.
    $department_list = get_departments();
    $your_administrations = $DB->get_records('department_administrators',
            array('userid' => $USER->id));

    $your_depts = array();
    foreach ($your_administrations as $administration) {
        $your_depts[$administration->department] = $department_list[$administration->department];
    }

    echo '<ol>';
    foreach ($your_depts as $code => $dept) {
        echo '<li><a href="evaluations.php?dept=' . $code . '">' . $dept . '</a></li>';
    }
    echo '</ol>';

    echo $OUTPUT->footer();
    die();
} else {
    //If a department is specified and the user is not an admin for that department
    //then print an error
    if (!is_dept_admin($dept, $USER)) {
        print_error(get_string('restricted', 'local_evaluations'));
    }
}

// ----- Output ----- //
echo $OUTPUT->header();

//Get the list of courses depending on which way they called this page.
if ($courseid !== false) {
    //If the specified a single course id then the coruse list will only be that course
    $sql = "SELECT * from {course} c WHERE c.id = $courseid";
    $courses = $DB->get_records_sql($sql);
} else if ($searchstring !== false) {
    //If search terms were entered then only look for courses with those terms.
    $searchterms = explode(" ", $searchstring);
    $courses = get_courses_search($searchterms, "fullname ASC", 0, 50,
            $totalcount);
} else {
    //If nothing was specified then we look at all courses in the system.
    $totalcount = $DB->count_records('course');
    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/evaluations.php?dept=' . $dept, array('perpage' => $perpage));
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
    $courses = get_courses_page($categoryid = "all", $sort = "c.fullname ASC",
            $fields = "c.*", $totalcount, $perpage * $page, $perpage);
}

//Start outputing the evaluation list table. (Holds all courses)
//Should be switched at some point so that each course gets it's own table =/
echo '<table width="95%" cellpadding="1" style="text-align: center;">';

foreach ($courses as $course) {
    //Make sure that the course is part of this department and the user is not
    //an instructor for this course.
    $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
    $is_instructor = has_capability('local/evaluations:instructor',
            $course_context);

    if (strpos($course->fullname, $dept) !== 0 || $is_instructor) {
        continue;
    }

    echo '<tr>';
    //Generate a link to the create a new evaluation form.
    $url = html_writer::link("$CFG->wwwroot/local/evaluations/evaluation.php" . "?cid=$course->id&dept=$dept",
                    get_string('new_single_evaluation', 'local_evaluations'));
    echo "<td colspan=8><b>$course->fullname </b><br> $url </td";

    echo '</tr>';

    //Output the table header.
    table_header();

    //Print a list of all evaluations for this course.
    $evals = $DB->get_records('evaluations',
            array('course' => $course->id, 'deleted' => 0));
    print_evaluations($evals);

    //Create a space between each course =/
    echo '<tr><td><br></td></tr>';
}

//Close the massive table
echo '</tr></table>';

//Create a search field.
echo "
     <center> <form action='$PAGE->url' method='post'>
      Search: <input type='text' name ='search'/>
      <input type='submit' />
      </form></center>
   ";

echo $OUTPUT->footer();

// ------ Functions ------ //

/**
 * Print out the table header row.
 */
function table_header() {
    echo '<tr>';
    echo '<th>' . get_string('name_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('start_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('end_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('status_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('delete_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('force_s_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('force_c_header', 'local_evaluations') . '</th>';
    echo '<th>' . get_string('response_count', 'local_evaluations') . '</th>';
    echo '</tr>';
}

/**
 * Prints the html table rows for each evaluation.
 * 
 * @param stdClass[] $evals An array of evaluations pulled from the database.
 */
function print_evaluations($evals) {
    global $dept;
    if ($evals == null || count($evals) == 0) {
        //If there were no evaluations let the users know.
        echo '<tr><td colspan=8>' . get_string('none', 'local_evaluations') . '</td></tr>';
        return;
    }

    foreach ($evals as $eval) {

        //create empty greyed out spans for force_ delete, start and complete
        $force_delete = '<span class="unavaliable_action">' . get_string('delete',
                        'local_evaluations') . '</span>';
        $force_start = '<span class="unavaliable_action">' . get_string('start',
                        'local_evaluations') . '</span>';
        $force_complete = '<span class="unavaliable_action">' . get_string('complete',
                        'local_evaluations') . '</span>';
        $reponses = 0;
        //Create the onclick confirm messages for delete, start and complete.
        $base = 'evaluation_action.php?dept=' . $dept . '&action=';
        $deleteconfirm = 'onclick="return confirm(\'' . get_string('confirm_delete',
                        'local_evaluations') . '\');"';
        $startconfirm = 'onclick="return confirm(\'' . get_string('confirm_start',
                        'local_evaluations') . '\');"';
        $completeconfirm = 'onclick="return confirm(\'' . get_string('confirm_complete',
                        'local_evaluations') . '\');"';

        switch (eval_check_status($eval)) {
            case EVAL_STATUS_PRESTART:
                //If it hasnt started replace the greyed out label with a href button for delete and start. Attach comfirm scripts.
                $force_delete = '<a href="' . $base . 'delete&eval_id=' . $eval->id . '" ' . $deleteconfirm . '>' . get_string('delete',
                                'local_evaluations') . '</a>';
                $force_start = '<a href="' . $base . 'force_start&eval_id=' . $eval->id . '" ' . $startconfirm . '>' . get_string('start',
                                'local_evaluations') . '</a>';
                break;
            case EVAL_STATUS_INPROGRESS:
                //If it is in progress replace the greyed out label with a href button for force_complete. Attach comfirm script.
                $force_complete = '<a href="' . $base . 'force_complete&eval_id=' . $eval->id . '" ' . $completeconfirm . '>' . get_string('complete',
                                'local_evaluations') . '</a>';
                break;
            case EVAL_STATUS_COMPLETE:
                //Leave greyed out.
                break;
        }

        //output the evaluation row.
        echo '<tr>';
        $href = $CFG->wwwroot . '/local/evaluations/evaluation.php?eval_id=' . $eval->id . '&dept=' . $dept;
        echo "<td><a href='$href'>" . $eval->name . "</a></td>";

        echo '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
        echo '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
        echo '<td>' . get_eval_status($eval) . '</td>';
        echo '<td>' . $force_delete . '</td>';
        echo '<td>' . $force_start . '</td>';
        echo '<td>' . $force_complete . '</td>';
        echo '<td>' . get_eval_reponses_count($eval->id) . '</td>';

        echo '</tr>';
    }
}

?>