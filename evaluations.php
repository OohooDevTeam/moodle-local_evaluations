<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG, $DB;

//$search = trim(optional_param('search', '', PARAM_NOTAGS));  // search string
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page

$searchstring = optional_param('search', NULL, PARAM_RAW);
$courseid = optional_param('cid', NULL, PARAM_INT);
$dept = optional_param('dept', false, PARAM_TEXT);


require_once('locallib.php');

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/evaluations.php');

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

if ($dept) {
    $navlinks[1]['link'] = $CFG->wwwroot . '/local/evaluations/evaluations.php';

    $navlinks[] = array(
        'name' => get_string('nav_ev_course', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    );
}

$nav = build_navigation($navlinks);

$PAGE->requires->css('/local/evaluations/style.css');

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

$PAGE->set_title(get_string('nav_ev_course', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_course', 'local_evaluations'));
require_login();

//Give a list of departments to choose from if they have not already specified one.
if (!$dept) {
    echo $OUTPUT->header();

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
    if (!is_dept_admin($dept, $USER)) {
        print_error(get_string('restricted', 'local_evaluations'));
    }
}

echo $OUTPUT->header();

if (isset($courseid)) {
    //for the actual searching
    $sql = "SELECT * from {course} c WHERE c.id = $courseid";
    $courses = $DB->get_records_sql($sql);
    //create a link for later on
    $singleCourseUrl = html_writer::link("$CFG->wwwroot/local/evaluations/evaluation.php" . "?cid=$courseid&dept=$dept",
                    get_string('new_single_evaluation', 'local_evaluations'));
} else if (isset($searchstring)) {
    $searchterms = explode(" ", $searchstring);
    $courses = get_courses_search($searchterms, "fullname ASC", 0, 50,
            &$totalcount);
    //print_r($output);
    // foreach ($output as $outputs) {
    //     $a = html_writer::link("$PAGE->url"."?cid=$outputs->id","$outputs->fullname");
    //     echo $a.'<br>';
    // }
} else {
    $totalcount = $DB->count_records('course');
    $url = new moodle_url($CFG->wwwroot . '/local/evaluations/evaluations.php', array('perpage' => $perpage));
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
    $courses = get_courses_page($categoryid = "all", $sort = "c.fullname ASC",
            $fields = "c.*", &$totalcount, $perpage * $page, $perpage);
}

/* copy save
  if (has_capability('local/evaluations:admin', $context)) {
  $totalcount = $DB->count_records('course');
  $url = new moodle_url($CFG->wwwroot . '/local/evaluations/evaluations.php', array('perpage' => $perpage));
  echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
  $courses = get_courses_page($categoryid = "all", $sort = "c.fullname ASC", $fields = "c.*", &$totalcount, $perpage * $page, $perpage);
  } else {
  $courses = get_instructing_courses();
  }
 */



if (empty($courses)) {
    print_error('restricted', 'local_evaluations');
}


// $href = $CFG->wwwroot . '/local/evaluations/evaluation.php';
//    echo "<a href='$href'>".get_string('new_evaluation','local_evaluations')."</a>";


echo '<table width="95%" cellpadding="1" style="text-align: center;">';



foreach ($courses as $course) {
    $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
    $is_instructor = has_capability('local/evaluations:instructor',
            $course_context);
    if (strpos($course->fullname, $dept) === 0 && !$is_instructor) {
        $evals = $DB->get_records('evaluations',
                array('course' => $course->id, 'deleted' => 0));
        echo '<tr>';
        if (isset($courseid)) {
            echo "<td colspan=8><b>$course->fullname </b><br> $singleCourseUrl</td>";
        } else {
            $url = html_writer::link("$CFG->wwwroot/local/evaluations/evaluation.php" . "?cid=$course->id&dept=$dept",
                            get_string('new_single_evaluation',
                                    'local_evaluations'));
            echo "<td colspan=8><b>$course->fullname </b><br> $url </td";
        }

        echo '</tr>';

        table_header();

        if ($evals == null) {
            echo '<tr><td colspan=8>' . get_string('none', 'local_evaluations') . '</td></tr>';
        } else {

            foreach ($evals as $eval) {
                $status = eval_check_status($eval);

                $force_delete = '<span class="unavaliable_action">' . get_string('delete',
                                'local_evaluations') . '</span>';
                $force_start = '<span class="unavaliable_action">' . get_string('start',
                                'local_evaluations') . '</span>';
                $force_complete = '<span class="unavaliable_action">' . get_string('complete',
                                'local_evaluations') . '</span>';
                $reponses = 0;

                $base = 'evaluation_action.php?dept=' . $dept . '&action=';
                $deleteconfirm = 'onclick="return confirm(\'' . get_string('confirm_delete',
                                'local_evaluations') . '\');"';
                $startconfirm = 'onclick="return confirm(\'' . get_string('confirm_start',
                                'local_evaluations') . '\');"';
                $completeconfirm = 'onclick="return confirm(\'' . get_string('confirm_complete',
                                'local_evaluations') . '\');"';

                if ($status == 1) {
                    $force_delete = '<a href="' . $base . 'delete&eval_id=' . $eval->id . '" ' . $deleteconfirm . '>' . get_string('delete',
                                    'local_evaluations') . '</a>';
                    $force_start = '<a href="' . $base . 'force_start&eval_id=' . $eval->id . '" ' . $startconfirm . '>' . get_string('start',
                                    'local_evaluations') . '</a>';
                    $reponses = 0;
                } elseif ($status == 2) {
                    $force_complete = '<a href="' . $base . 'force_complete&eval_id=' . $eval->id . '" ' . $completeconfirm . '>' . get_string('complete',
                                    'local_evaluations') . '</a>';
                    $reponses = get_eval_reponses_count($eval->id);
                } elseif ($status == 3) {
                    $reponses = get_eval_reponses_count($eval->id);
                }

                echo '<tr>';
                $href = $CFG->wwwroot . '/local/evaluations/evaluation.php?eval_id=' . $eval->id . '&dept=' . $dept;
                echo "<td><a href='$href'>" . $eval->name . "</a></td>";

                echo '<td>' . date('F j Y @ G:i', $eval->start_time) . '</td>';
                echo '<td>' . date('F j Y @ G:i', $eval->end_time) . '</td>';
                echo '<td>' . get_eval_status($eval) . '</td>';
                echo '<td>' . $force_delete . '</td>';
                echo '<td>' . $force_start . '</td>';
                echo '<td>' . $force_complete . '</td>';
                echo '<td>' . $reponses . '</td>';

                echo '</tr>';
            }
        }
        echo '<tr><td><br></td></tr>';
    }
}


echo '</tr></table>';

//james start feb 21   

echo "
     <center> <form action='$PAGE->url' method='post'>
      Search: <input type='text' name ='search'/>
      <input type='submit' />
      </form></center>
   ";

//end james 
echo $OUTPUT->footer();

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

?>
