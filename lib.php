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
require_once($CFG->dirroot . '/local/evaluations/locallib.php');

function local_evaluations_cron() {
    global $DB;

//early_semester_messages();  

    $evaluations = $DB->get_records('evaluations',
            array('complete' => 0, 'deleted' => 0));

    foreach ($evaluations as $eval) {
        if (eval_check_status($eval) == 2) { //will complete evals, or return status
            //its inprogress - need to send reminders
            $course = $DB->get_record('course', array('id' => $eval->course));

            send_student_reminders($eval, $course);
        }
    }

    process_mail_que();
}

//event called when an eval is set as complete
function eval_complete_handler($event) {
    global $DB;
//send an email to the instructor informing them its complete
    //include a report with anonymous user responses 
    //Tell them number of responses

    $eval = $DB->get_record('evaluations', array('id' => $event->eval_id));
//eval_complete_message($eval);
    return true;
}

//event called when an eval is created
function eval_created_handler($event) {
    //check if inviliators - send them an email with date

    return true;
}

?>
