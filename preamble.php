<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG;

require_once('locallib.php');

$eval_id = required_param('eval_id', PARAM_INT);
//security check
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);



$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    ),
);

$nav = build_navigation($navlinks);


$PAGE->set_title(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();

$admin_access = has_capability('local/evaluations:admin', $context);
$courses = get_instructing_courses();

//Display Form
echo $OUTPUT->header();

//display preamble
$eval = $DB->get_record('evaluations', array('id' => $eval_id));
if ($record = $DB->get_record_select('department_preambles',
        "department='$eval->department'")) {
    echo '<pre style="padding:20px white-space: pre-wrap;       /* css-3 */
 white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
 white-space: -pre-wrap;      /* Opera 4-6 */
 white-space: -o-pre-wrap;    /* Opera 7 */
 word-wrap: break-word;       /* Internet Explorer 5.5+ */">' . $record->preamble . '</pre>';
} else {
    echo "<div style='padding:20px'>Dear Students,<br><br>

Thank you for assisting us by providing feedback to Faculty regarding their teaching.  The feedback is an important part of the evaluative process engaged in by individual instructors and by the Faculty generally.  Copies of all course evaluations are forwarded to the individual instructor and go to the Dean’s office for consideration in program planning and development.  Please be assured that the comments you make are confidential and will be provided to individual instructors only after final grades for the course have been submitted to the Dean’s office and approved.  At that time the instructor will receive a numerical summary of the responses to the questionnaire and a list of all comments provided about the course.  These comments are listed numerically without identifying the writer.
<br><br>
The Faculty appreciates your willingness to participate in this process.
</div>";
}
//display button to continue or not
$href = $CFG->wwwroot . '/local/evaluations/response.php?eval_id=' . $eval_id;
$back = $CFG->wwwroot . '/local/evaluations/evals.php';
echo "<center>";
echo "
	<form method='post' action='$href'>
		<input type='submit' value = 'Continue'>
	</form>
<br>
	<form method='get' action='$back'>
		<input type='submit' value = 'Go Back'>
	</form>	
";
echo "</center>";

echo $OUTPUT->footer();
?>