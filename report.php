<?php

require_once('../../config.php');
global $DB, $PAGE, $CFG, $USER;
require_once('lib.php');
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');
require_once('classes/anonym_report_PDF.php');
require_once('classes/admin_report_PDF.php');

require_once('locallib.php');

$evaluation = required_param('evalid', PARAM_INT);
$type = optional_param('type', 'anonymous', PARAM_TEXT);
$download = optional_param('force','I',PARAM_ALPHA);
$dept = required_param('dept', PARAM_TEXT);

$eval = $DB->get_record('evaluations', array('id' => $evaluation));

if (!$eval) {
    print_error(get_string('eval_id_invalid', 'local_evaluations'));
}

$course = $DB->get_record('course', array('id' => $eval->course));

if (!$course) {
    print_error(get_string('invalid_course', 'local_evaluations'));
}

$course_context = get_context_instance(CONTEXT_COURSE, $course->id);
$system_context = get_context_instance(CONTEXT_COURSE, $course->id);
//require_login($course->id);

$is_instructor = has_capability('local/evaluations:instructor', $course_context);
$is_admin = is_dept_admin($dept, $USER);

if ($is_instructor || !$is_admin) {
    print_error(get_string('restricted', 'local_evaluations'));
}

if(eval_check_status($eval) != 3){//check if complete
   print_error(get_string('not_completed', 'local_evaluations')); 
}

//get teacher of course
//right now it will get the last teacher in a course
//maybe we should make it compile a list if there are more than 1 teachers.

$teacherinfo = get_role_users(3, $course_context);
$teacher = new stdClass();
foreach($teacherinfo as $t){
    $teacher->name = $t->firstname . " " . $t->lastname;
}

switch ($type) {
    case 'anonymous':
        anonymous_report($eval, $course, $teacher, $download);
        break;

    case 'admin':
        //admin_report($eval, $course, $is_admin,$download);
         anonymous_report($eval, $course, $teacher, $download);
        break;
}



function anonymous_report($eval, $course,$teacher,$download) {
    global $dept;
    ob_start(); //strange output from get_String that breaks pdf output unless we dump it
    $pdf = new Anonym_report_PDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $eval, $course,$teacher, $dept);
    ob_end_clean(); //dump output this far
    $reportName = $course->fullname."report";
    $pdf->Output($reportName, $download);
}

function admin_report($eval, $course, $is_admin,$download) {

    if (!$is_admin) {
        print_error(get_string('restricted', 'local_evaluations'));
    }

    ob_start(); //strange output from get_String that breaks pdf output unless we dump it
    $pdf = new admin_report_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $eval, $course);
    ob_end_clean(); //dump output this far
    $pdf->Output('report.pdf', $download);
}

?>
