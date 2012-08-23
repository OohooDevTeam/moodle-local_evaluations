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
 * This is the page that acctually generates the pdfs for download or viewing.
 */
require_once('../../config.php');
require_once('lib.php');
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');
require_once('classes/anonym_report_PDF.php');
require_once('locallib.php');

// ----- Parameters ----- //
$evaluation = required_param('evalid', PARAM_INT);
$download = optional_param('force', 'I', PARAM_ALPHA);
$dept = required_param('dept', PARAM_TEXT);
$eval = $DB->get_record('evaluations', array('id' => $evaluation));
$course = $DB->get_record('course', array('id' => $eval->course));
$course_context = get_context_instance(CONTEXT_COURSE, $course->id);
$system_context = get_context_instance(CONTEXT_COURSE, $course->id);

// ----- Security ----- //
require_login();
$is_instructor = has_capability('local/evaluations:instructor', $course_context);
$is_admin = is_dept_admin($dept, $USER);

if ($is_instructor || !$is_admin) {
    print_error(get_string('restricted', 'local_evaluations'));
}

if (eval_check_status($eval) != EVAL_STATUS_COMPLETE) {//check if complete
    print_error(get_string('not_completed', 'local_evaluations'));
}


if (!$eval) {
    print_error(get_string('eval_id_invalid', 'local_evaluations'));
}


if (!$course) {
    print_error(get_string('invalid_course', 'local_evaluations'));
}

// ----- Output ----- //
//get teacher of course
//right now it will get the last teacher in a course
//maybe we should make it compile a list if there are more than 1 teachers.
$teacherinfo = get_role_users(3, $course_context);
$teacher = new stdClass();
foreach ($teacherinfo as $t) {
    $teacher->name = $t->firstname . " " . $t->lastname;
}

//Create a report pdf.
ob_start(); //strange output from get_String that breaks pdf output unless we dump it
$pdf = new Anonym_report_PDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $eval, $course, $teacher, $dept);
ob_end_clean(); //dump output this far
$reportName = $course->fullname . "report";
$pdf->Output($reportName, $download);
?>
