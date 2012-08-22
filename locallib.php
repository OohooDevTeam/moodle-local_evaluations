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
define('EVAL_STATUS_DELETED', -4);
define('EVAL_STATUS_ENDTIME_LESS_STARTTIME', -3);
define('EVAL_STATUS_COMPLETE_BEFORE_ENDTIME', -1);
define('EVAL_STATUS_ERROR', 0);
define('EVAL_STATUS_PRESTART', 1);
define('EVAL_STATUS_INPROGRESS', 2);
define('EVAL_STATUS_COMPLETE', 3);

/**
 * Get the courses that the given user is teaching
 * @global stdClass $USER
 * @return stdClass the result from the moodle function get_user_courses_bycap
 */
function get_instructing_courses() {
    global $USER;
    return get_user_courses_bycap($USER->id, 'local/evaluations:instructor',
                    array(), false, $sort = 'c.sortorder ASC', NULL, $limit = 20);
}

/**
 * Creates an html string that represents the given object.
 * 
 * @param  mixed $eval Either an object that can be grabed from the evaluations table
 *              or an int that is the evaluation id from that table.
 * @return string HTML
 */
function get_eval_status($eval) {
    $status = '';

    switch (eval_check_status($eval)) {

        case EVAL_STATUS_COMPLETE:
            $status .= '<span class="eval_status complete">';
            $status .= get_string('complete', 'local_evaluations');
            $status .= '</span>';
            break;

        case EVAL_STATUS_COMPLETE_BEFORE_ENDTIME:
            $status .= '<span class="eval_status error">';
            $status .= 'error: Completed before end_time';
            $status .= '</span>';
            break;

        case EVAL_STATUS_ENDTIME_LESS_STARTTIME:
            $status .= '<span class="eval_status error">';
            $status.='Error: end is less(equal) than the start time';
            $status .= '</span>';
            break;

        case EVAL_STATUS_DELETED:
            $status .= '<span class="eval_status error">';
            $status.='Error: Evaluation deleted';
            $status .= '</span>';
            break;

        case EVAL_STATUS_INPROGRESS:
            $status .= '<span class="eval_status inprogress">';
            $status .= get_string('inprogress', 'local_evaluations');
            $status .= '</span>';
            break;

        case EVAL_STATUS_PRESTART:
            $status .= '<span class="eval_status prestart">';
            $status .= get_string('beforestart', 'local_evaluations');
            $status .= '</span>';
            break;

        case EVAL_STATUS_ERROR:
            $status .= '<span class="eval_status error">';
            $status = get_string('Error', 'local_evaluations');
            $status .= '</span>';
            break;
    }

    return $status;
}

/**
 * Gets the status of an evaluation. These are all the possible statuses
 * 
 * EVAL_STATUS_DELETED
 * EVAL_STATUS_ENDTIME_LESS_STARTTIME
 * EVAL_STATUS_COMPELTE_BEFORE_ENDTIME
 * EVAL_STATUS_ERROR
 * EVAL_STATUS_PRESTART
 * EVAL_STATIS_INPROGRESS
 * EVAL_STATUS_COMPELTE
 * 
 * @global moodle_database $DB
 * @param  mixed $eval Either an object that can be grabed from the evaluations table
 *              or an int that is the evaluation id from that table.
 * @return int  The current status of the evaluation.
 */
function eval_check_status($eval) {
    global $DB;
    $status = '';
    $current_time = time();

    if (is_number($eval)) {

        if ($eval > 0) {
            $eval = $DB->get_record('evaluations', array('id' => $eval));
        } else {
            print_error('PROGRAMMER: eval_check_status recieved an id of 0!');
        }
    }

    if ($eval->deleted == 1) {
        return -4;
    }

    //Applies to Both
    if ($eval->end_time <= $eval->start_time) {//check if start >= end
        return -3;
    }

    if ($eval->complete == 1) { //ONLY COMPLETE
        if ($eval->end_time > $current_time) {
            return EVAL_STATUS_COMPLETE_BEFORE_ENDTIME;
        }

        return EVAL_STATUS_COMPLETE;
    } elseif ($eval->complete == 0) { //Not COMPLETE
        $current_time = time();


        if ($eval->end_time < $current_time) {//check if end is already past
            //If it has then set the evaluation as complete.
            if (set_eval_complete($eval) > 0) {
                //The above will have returned 1 if update was successful.
                
                //Now that it was marked as complete let's check the status again.
                return eval_check_status($eval);
            }
        }


        //in progress
        if ($eval->start_time <= $current_time && $eval->end_time > $current_time) {
            return EVAL_STATUS_INPROGRESS;
        }

        //not started
        if ($eval->start_time >= $current_time && $eval->end_time > $current_time) {
            return EVAL_STATUS_PRESTART;
        }
    }

    return EVAL_STATUS_ERROR;
}

/**
 * Set the given evaluation as complete.
 * 
 * @global moodle_database $DB
 * @param stdClass $eval a object that represents an entry in the evaluations table.
 * @return int 1 if successful 0 otherwise.
 */
function set_eval_complete($eval) {
    global $DB;
    
    //Create the database entry 
    $new_eval = new stdClass();
    $new_eval->id = $eval->id;
    $new_eval->complete = 1;

    //Update the database.
    if ($DB->update_record('evaluations', $new_eval)) {
        
        //Triger an event... for some reason...
        $eventdata = new object();
        $eventdata->component = 'local/evaluations';    // path in Moodle
        $eventdata->name = 'eval_complete';        // type of message from that module (as module defines it)
        $eventdata->eval_id = $eval->id; 

        events_trigger('eval_complete', $eventdata);
        
        return 1;
    }
    //If update failed then return 0.
    return 0;
}

/**
 * Count the number of responses for the evaluation with the given id.
 * 
 * @global moodle_database $DB
 * @param int $eval_id an evaluation id.
 * @return int The number of responses to the given evaluation.
 */
function get_eval_reponses_count($eval_id) {
    global $DB;

    $sql = "SELECT count(DISTINCT r.user_id) FROM {evaluation_response} r,{evaluation_questions} q,{evaluations} e
  WHERE r.question_id = q.id AND q.evalid = e.id AND q.evalid = $eval_id";

    return $DB->count_records_sql($sql);
}

/**
 * 
 * @global type $DB
 * @param type $eval_id
 * @return type 
 */
function get_eval_uids($eval_id) {
    global $DB;

    $SQL = "SELECT DISTINCT user_id FROM {evaluation_response} r,{evaluation_questions} q,{evaluations} e
  WHERE r.question_id = q.id AND q.evalid = e.id AND q.evalid = $eval_id";

    return $DB->get_records_sql($SQL);
}

/**
 *
 * @global type $DB
 * @param type $evalid
 * @param type $userid
 * @return type 
 * 
 * This function needs to be modified still. We should probably ignore question types that have comments of any sort.
 * Ignores comment type questions. they have id 1 (or they should).
 * Sql can probably sort that out. Just sayin
 */
function get_responses_for_user($evalid, $userid) {
    global $DB;

    $SQL = "SELECT question_id,response FROM {evaluation_response} r,{evaluation_questions} q,{evaluations} e, {evaluations_question_types} qt
  WHERE r.user_id = $userid AND r.question_id = q.id AND q.evalid = e.id AND q.evalid = $evalid AND q.type = qt.id AND qt.class != 'comment' AND qt.class != 'years'"; // ORDERD BY question_id ASC";


    return $DB->get_records_sql($SQL);
}

function get_user_comments($evalid, $userid) {
    global $DB;

    $SQL = "SELECT question_id,question_comment, q.question FROM {evaluation_response} r,{evaluation_questions} q,{evaluations} e, {evaluations_question_types} qt
  WHERE r.user_id = $userid AND r.question_id = q.id AND q.evalid = e.id AND q.evalid = $evalid AND q.type = qt.id AND (qt.class = 'comment' OR qt.class = 'years') ORDER BY question_id ASC"; // ORDERD BY question_id ASC";


    return $DB->get_records_sql($SQL);
}

/**
 * Selects the number of standard questions
 */
function count_std_questions($dept) {
    global $DB;

    $SQL = "SELECT COUNT(*) FROM {evaluation_standard_question} esq, {evaluations_question_types} qt WHERE esq.department = '$dept' AND esq.type = qt.id AND qt.class != 'comment'  AND qt.class != 'years'"; // ORDERD BY question_id ASC";

    return $DB->count_records_sql($SQL);
}

function delete_eval($eval_id) {
    global $DB;

    $status = eval_check_status($eval_id);

    if ($status != 1) {
        return false;
    }

    $eval = new stdClass();
    $eval->id = $eval_id;
    $eval->deleted = 1;

    $DB->update_record('evaluations', $eval);
}

function force_start_eval($eval_id) {
    global $DB;

    $status = eval_check_status($eval_id);

    if ($status != 1) {
        return false;
    }

    $eval = new stdClass();
    $eval->id = $eval_id;
    $eval->start_time = time();

    $DB->update_record('evaluations', $eval);
}

//same as set_eval_complete except it only needs id
//could most likley be merged with set_eval_complete,
//but I'm not sure if they where split for a specific reason
//@TODO determine if functions can be joined
function force_complete_eval($eval_id) {
    global $DB;

    $status = eval_check_status($eval_id);

    if ($status != 2) {
        return false;
    }

    $eval = new stdClass();
    $eval->id = $eval_id;
    $eval->end_time = time();
    $eval->complete = 1;

    if ($DB->update_record('evaluations', $eval)) {

        $eventdata->component = 'local/evaluations';    // path in Moodle
        $eventdata->name = 'eval_complete';        // type of message from that module (as module defines it)
        $eventdata->eval_id = $eval_id;      // user object
        events_trigger('eval_complete', $eventdata);
    }
}

//Creates the repeatable template designed to edit questions in evaluations
//Note that this does not load the data, that is done in the question object.
function questionCreation_mform(&$mform) {
    global $DB;
    $repeatarray = array();

    $repeatarray[] = &$mform->createElement('static', 'question_achor',
                    '<a name="q{no}"></a>', '');
    //Question Header
    $repeatarray[] = &$mform->createElement('header', 'question_header_x',
                    get_string('question', 'local_evaluations') . ' {no}');

    //Question Dialog
    $repeatarray[] = &$mform->createElement('textarea', 'question_x',
                    get_string('question_c', 'local_evaluations'),
                    array('rows' => 8, 'cols' => 65));
    //$repeatarray[] = &$mform->createElement('htmleditor', 'question_x', get_string('question_c', 'local_evaluations'));
    //Question Types
    $question_types = $DB->get_records('evaluations_question_types');
    $question_types_choices = array();
    //print_object(&$mform);exit();
    foreach ($question_types as $id => $question_type) {
        $question_types_choices[$id] = $question_type->name;
    }

    $attributes = array();
    $repeatarray[] = &$mform->createElement('select', 'question_type_id',
                    get_string('type_c', 'local_evaluations'),
                    $question_types_choices, $attributes);
    //james down up delete button
    $mform->registerNoSubmitButton('delete_question_x');
    $mform->registerNoSubmitButton('swapup_question_x');
    $mform->registerNoSubmitButton('swapdown_question_x');

    $repeatarray[] = $mform->createElement('html',
            '<div class="question_controls">');
    $repeatarray[] = $mform->createElement('submit', 'delete_question_x',
            get_string('delete'));
    $repeatarray[] = $mform->createElement('html', '</div>');

    $repeatarray[] = $mform->createElement('html',
            '<div class="question_controls">');
    $repeatarray[] = $mform->createElement('submit', 'swapup_question_x',
            get_string('up'));
    $repeatarray[] = $mform->createElement('html', '</div>');

    $repeatarray[] = $mform->createElement('html',
            '<div class="question_controls">');
    $repeatarray[] = $mform->createElement('submit', 'swapdown_question_x',
            get_string('down'));
    $repeatarray[] = $mform->createElement('html', '</div>');

    //Hidden
    //$repeatarray[] = &$mform->createElement('hidden', 'position_x', 0);        
    $repeatarray[] = &$mform->createElement('hidden', 'questionid_x', 0);
    $repeatarray[] = &$mform->createElement('hidden', 'question_std', 0);

    return $repeatarray;
}

function process_question_postdata($fromform) {

    if (isset($fromform->questionid_x)) {
        $question_ids = $fromform->questionid_x;
    } else {
        $question_ids = array();
    }
    $questions = array();

    if (!isset($fromform->question_x)) {
        $question_text = array();
    } else {
        $question_text = $fromform->question_x;
    }
    foreach ($question_ids as $order => $question_id) {

        if (trim($question_text[$order]) == '' && $question_ids[$order] == 0)
            continue;

        $questions[$order] = (object) array('id' => $fromform->questionid_x[$order],
                    'question' => $question_text[$order], 'type' => $fromform->question_type_id[$order], 'question_order' => $order, 'isstd' => $fromform->question_std[$order]);
    }

    return $questions;
}

//conditional - returned array from post IF button pressed
//class - The class of function to be created
//function - the function to be called on that class object
function question_button_event($conditional, $function, $class,
        $parameter = null) {
    $returnQuestion = false;

//delete button pressed
    if (isset($_REQUEST[$conditional])) {
        $question_id = $_REQUEST['questionid_x'];
        $delete_buttons = $_REQUEST[$conditional];
        foreach ($delete_buttons as $order => $delete_button) {
            $returnQuestion = $order;
            $returnQuestion++;
            if ($question_id[$order] == 0)
                continue;
            $object = new $class($question_id[$order]);
            $object->$function($parameter);
        }
    }


    if ($returnQuestion === false) {
        return false;
    }



    if ($function == 'delete' || $function == 'order_swapup') {
        $returnQuestion--;
    } elseif ($function == 'order_swapdown') {
        $returnQuestion++;
    }

    if ($returnQuestion < 1) {
        $returnQuestion = 1;
    }


    return '#q' . $returnQuestion++;
}

function process_reponse_postdata($fromform) {

    $responses = array();
    foreach ($fromform->questionid as $order => $id) {
        $response = new stdClass();
        $response->question_id = $id;
        $response->response = $fromform->response[$order];
        if (isset($fromform->comments[$order])) {
            $response->question_comment = $fromform->comments[$order];
        } else {
            $response->question_comment = 'asdf';
        }
        $responses[] = $response;
    }

    return $responses;
}

//Used by db/install.php and db/upgrate.php
function update_question_types() {
    global $CFG;

    $dir = $CFG->dirroot . '/local/evaluations/classes/question_types/';
    $dirHandle = opendir($dir);

    while (($file = readdir($dirHandle)) !== false) {

        if (is_dir($file)) {
            continue;
        }

        $extension = explode('.', $file);
        $extension = $extension[count($extension) - 1];
        if ($extension != 'php') {
            continue;
        }

        if (substr($file, 0, 9) != 'question_') {
            continue;
        }



        process_question_type_file($dir, $file);
    }
    closedir($dirHandle);
}

//Not going to remove question types - since it will break existing questions....
//Used by db/install.php and db/upgrade.php
function process_question_type_file($dir, $file) {
    global $DB;

    include_once($dir . $file);

    $class_name = explode('.', $file);
    $class_name = $class_name[0];

    if (class_exists($class_name)) {//check if class exists question_X
        $class = new $class_name(false);
        if (method_exists($class, 'display')) { //check if display function exists
            $sql = "SELECT * from {evaluations_question_types} qt WHERE qt.class = '" . substr($class_name,
                            9) . "'";
            $type = $DB->get_record_sql($sql);

            $questionType = new stdClass();
            $questionType->name = $class->type_name;
            $questionType->class = substr($class_name, 9);

            if ($type) {
                $questionType->id = $type->id;

                $DB->update_record('evaluations_question_types', $questionType);
            } else {
                $DB->insert_record('evaluations_question_types', $questionType);
            }
        }
    }
}

//Function will be called from a cron controlled function
function send_student_reminders($eval, $course) {
    global $DB, $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $eval->course);
    $contextlists = get_related_contexts_string($context);

    if (!$eval->email_students)
        return;

    //I don't like how this is done, but attempts to do this with capabilities didn't work as planned in certian cases :/
    $student_role = $DB->get_record('role', array('shortname' => 'student'));

    $current = time();
    $limit = time() - (86400 * $CFG->local_eval_message_que_limit); //2 days
    //first select all users for the course for that eval
    //find their roles in course, and remove non-students
    //remove all students in eval that have responded
    //remove if they have had an email from this eval in the last day 
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email,el.evalid, el.end_sent, el.student_reminders, el.id as elog_id 
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            LEFT JOIN {evaluations_email_log} el ON el.userid = u.id and el.evalid = $eval->id
            WHERE  u.deleted = 0 AND u.confirmed = 1
                AND ra.contextid $contextlists                  
                AND (el.student_reminders IS NULL OR el.student_reminders < $limit OR el.student_reminders = 0)
                AND ra.roleid = $student_role->id  AND u.id NOT IN
            
                    (SELECT r2.user_id 
                    FROM {evaluation_questions} q2, {evaluation_response} r2 
                    WHERE r2.question_id = q2.id 
                    AND q2.evalid = $eval->id 
                    AND r2.user_id = u.id)";

    $users = $DB->get_records_sql($sql);


    foreach ($users as $student) {

        //check to see if first time sending message
        if ($student->elog_id == null) {
            $log = new stdClass();
            $log->student_reminders = 0;
            $log->end_sent = 0;
            $log->evalid = $eval->id;
            $log->userid = $student->id;
            $student->elog_id = $DB->insert_record('evaluations_email_log', $log);
        }

        $evals_url = $CFG->wwwroot . '/local/evaluations/evals.php';

        //insert message into que!
        $email = new stdClass();
        $email->userto = $student->id;
        $email->from_title = $course->shortname;
        $email->subject = get_string('email_new_evaluation', 'local_evaluations') . $course->shortname;
        $email->body = get_string('email_body', 'local_evaluations') . ' ' . $course->fullname . ' ' . '\n\r' . $evals_url;
        $email->body_html = get_string('email_body_html', 'local_evaluations') . ' ' . $course->fullname . ' ' . '<br/>' . $evals_url;
        $email->date_queued = $current;
        $email->log_id = $student->elog_id;
        $email->email_type = 'student_reminders';
        $DB->insert_record('evaluations_mail_que', $email);
    }
}

function process_mail_que() {
    global $DB, $CFG;
    $limit = time() - (86400 * $CFG->local_eval_message_que_limit); //2 days
    $current_time = time();

    $message_que = $DB->get_records('evaluations_mail_que');


    foreach ($message_que as $message) {

        if ($message->date_queued < $limit) { //if qued more than 2 days ago - then cron hasn't been on / working therefore remove them OR message send failing
            remove_queued_email($message->id);
            continue;
        }


        //load logging based on type of message
        if ($message->email_type == 'early_reminder') { // early reminder
            $email_log = $DB->get_record('evaluations_early_reminder',
                    array('id' => $message->log_id));
            if ($email_log->date_sent > $limit) { //if send a reminder in last 2 days - don't spam them!
                remove_queued_email($message->id);
                continue;
            }
        } elseif ($message->email_type == 'student_reminders') { //student reminder
            $email_log = $DB->get_record('evaluations_email_log',
                    array('id' => $message->log_id));
            if ($email_log->student_reminders > $limit) { //if send a reminder in last 2 days - don't spam them!
                remove_queued_email($message->id);
                continue;
            }
        } elseif ($message->email_type == 'complete') { //student reminder
            $email_log = $DB->get_record('evaluations_email_log',
                    array('id' => $message->log_id));
            if ($email_log->end_sent > $limit) { //if send a reminder in last 2 days - don't spam them!
                remove_queued_email($message->id);
                continue;
            }
        }

        $user = $DB->get_record('user', array('id' => $message->userto));

        if (email_to_user($user, $message->from_title, $message->subject,
                        $message->body, $message->body_html, $attachment = '',
                        $attachname = '', $usetrueaddress = false,
                        $replyto = '', $replytoname = '', $wordwrapwidth = 79)) {



            $log = new stdClass();
            $log->id = $email_log->id;

            //update logs based on email type
            if ($message->email_type == 'early_reminder') { //early reminder
                $log->date_sent = $current_time;
                $elog_id = $DB->update_record('evaluations_early_reminder', $log);
            } elseif ($message->email_type == 'student_reminders') { //student reminders
                $log->student_reminders = $current_time;
                $elog_id = $DB->update_record('evaluations_email_log', $log);
            } elseif ($message->email_type == 'complete') { //student reminders
                $log->end_sent = $current_time;
                $elog_id = $DB->update_record('evaluations_email_log', $log);
            }

            remove_queued_email($message->id);
        } else {
            print 'error: ' . $message->id;
            //error sending message  
        }
    }
}

function remove_queued_email($queued_id) {
    global $DB;
    $DB->delete_records('evaluations_mail_que', array('id' => $queued_id));
}

function early_semester_messages() {
    global $DB, $CFG;

    $time = time();
    $early_email_delay = $CFG->local_eval_early_message_delay * 24 * 60 * 60; //  delay in days * 24 hours * 60 mins * 60 secs
    //select all course where startdate + delay < time
    //remove all courses that have been sent a reminder(not qued a message - but sent a message)
    $sql = "SELECT * FROM {course} c
WHERE c.id <> 1 AND (c.startdate + $early_email_delay) < $time AND c.id NOT IN
                (SELECT er.course
                 FROM {evaluations_early_reminder} er 
                 WHERE er.date_sent > 0)";

    $courses = $DB->get_records_sql($sql);

//no courses to send messages
    if ($courses == null)
        return;

    foreach ($courses as $course) {

        //check that an log has been created
        if (!$log_id = $DB->get_record('evaluations_early_reminder',
                array('course' => $course->id))) {
            //We know they created one so we marked as reminded!

            $log_id = new stdClass();
            $er = new stdClass();
            $er->course = $course->id;
            $er->date_sent = 0;
            $log_id->id = $DB->insert_record('evaluations_early_reminder', $er);
        }

        //set context for current course
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $instructors = get_users_by_capability($context,
                'local/evaluations:instructor',
                $fields = 'u.id, u.firstname, u.lastname, u.email');


        $evals_url = $CFG->wwwroot . '/local/evaluations/evaluations.php';

        if (!$instructors)
            continue; // no instructors for course (shouldn't happen...)

        foreach ($instructors as $instructor) {



            //insert message into que!                        
            $email = new stdClass();
            $email->userto = $instructor->id;
            $email->from_title = $course->shortname;
            $email->subject = get_string('email_early_evaluation',
                            'local_evaluations') . $course->shortname;
            $email->body = get_string('email_early_body', 'local_evaluations') . ' ' . $course->fullname . '. ' . '\n\r' . $evals_url;
            $email->body_html = get_string('email_early_body_html',
                            'local_evaluations') . ' ' . $course->fullname . '. ' . '<br/>' . $evals_url;
            $email->date_queued = $time;
            $email->log_id = $log_id->id;
            $email->email_type = 'early_reminder';

            $DB->insert_record('evaluations_mail_que', $email);
        }
    }
}

function eval_complete_message($eval) {
    global $DB, $CFG;
    $report = $CFG->wwwroot . '/local/evaluations/report.php?evalid=' . $eval->id;
    $time = time();
    //$report = get_anonymous_report($eval); //return an url for report (not sure)
    //set context for current course
    $context = get_context_instance(CONTEXT_COURSE, $eval->course);
    $instructors = get_users_by_capability($context,
            'local/evaluations:instructor',
            $fields = 'u.id, u.firstname, u.lastname, u.email');


    $evals_url = $report;

    if (!$instructors)
        return; // no instructors for course (shouldn't happen...)

    $course = $DB->get_record('course', array('id' => $eval->course));

    $body = get_string('email_complete_body', 'local_evaluations') . ' ' . $eval->name . ' (' . $course->fullname . ') '
            . '\n\r'
            . get_string('email_num_reports', 'local_evaluations') . ' ' . get_eval_reponses_count($eval->id) . '.'
            . '\n\r'
            . $evals_url;
    $html_body = get_string('email_complete_body', 'local_evaluations') . ' ' . $eval->name . " (" . $course->fullname . ') '
            . '<br/>'
            . get_string('email_num_reports', 'local_evaluations') . ' ' . get_eval_reponses_count($eval->id) . '.'
            . '<br/>'
            . $evals_url;

    foreach ($instructors as $instructor) {
        //insert message into que!  

        $log_id = $DB->get_record('evaluations_email_log',
                array('evalid' => $eval->id, 'userid' => $instructor->id));



        if (!$log_id) {
            $log_id = new stdClass();

            //CREATE LOG
            $log = new stdClass();
            $log->student_reminders = 0;
            $log->end_sent = 0;
            $log->evalid = $eval->id;
            $log->userid = $instructor->id;

            //Pass log to message
            $log_id->id = $DB->insert_record('evaluations_email_log', $log);
        }

        $email = new stdClass();
        $email->userto = $instructor->id;
        $email->from_title = $course->shortname;
        $email->subject = get_string('email_complete_evaluation',
                        'local_evaluations') . $course->shortname;
        $email->body = $body;
        $email->body_html = $html_body;
        $email->date_queued = $time;
        $email->log_id = $log_id->id;
        $email->email_type = 'complete';

        $DB->insert_record('evaluations_mail_que', $email);
    }
}

function mmmr($array, $output = 'mean') {
    if (!is_array($array)) {
        return FALSE;
    } else {
        switch ($output) {
            case 'mean':
                $count = count($array);
                $sum = array_sum($array);
                $total = $sum / $count;
                break;
            case 'median':
                rsort($array);
                $middle = round(count($array) / 2);
                $total = $array[$middle - 1];
                break;
            case 'mode':
                $v = array_count_values($array);
                arsort($v);
                foreach ($v as $k => $v) {
                    $total = $k;
                    break;
                }
                break;
            case 'range':
                sort($array);
                $sml = $array[0];
                rsort($array);
                $lrg = $array[0];
                $total = "$sml - $lrg";
                break;
        }
        return $total;
    }
}

function count_students_in_course($courseid) {
    global $DB;

    $student_role = $DB->get_record('role', array('shortname' => 'student'));
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $contextlists = get_related_contexts_string($context);

    $sql = "SELECT count(u.id)
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            WHERE  u.deleted = 0 AND u.confirmed = 1
                AND ra.contextid $contextlists                  
                AND ra.roleid = $student_role->id";

    $student_count = $DB->count_records_sql($sql);

    return $student_count;
}

function date_diff_format($d1, $d2) {


    $diff_secs = abs($d1 - $d2);
    $base_year = min(date("Y", $d1), date("Y", $d2));

    $diff = mktime(0, 0, $diff_secs, 1, 1, $base_year);

    $array = array(
        "years" => date("Y", $diff) - $base_year,
        "months_total" => (date("Y", $diff) - $base_year) * 12 + date("n", $diff) - 1,
        "months" => date("n", $diff) - 1,
        "days_total" => floor($diff_secs / (3600 * 24)),
        "days" => date("j", $diff) - 1,
        "hours_total" => floor($diff_secs / 3600),
        "hours" => date("G", $diff),
        "minutes_total" => floor($diff_secs / 60),
        "minutes" => (int) date("i", $diff),
        "seconds_total" => $diff_secs,
        "seconds" => (int) date("s", $diff)
    );

    $duration = '';

    if ($array['years'] > 0) {
        $duration .= $array['years'] . ' ' . get_string('years',
                        'local_evaluations');
    }

    if ($array['months'] > 0) {
        $duration .= $array['months'] . ' ' . get_string('months',
                        'local_evaluations');
    }

    if ($array['days'] > 0) {
        $duration .= $array['days'] . ' ' . get_string('days',
                        'local_evaluations');
    }

    if ($array['hours'] > 0) {
        $duration .= $array['hours'] . ' ' . get_string('hours',
                        'local_evaluations');
    }

    if ($array['minutes'] > 0) {
        $duration .= $array['minutes'] . ' ' . get_string('minutes',
                        'local_evaluations');
    }

    if ($array['seconds'] > 0) {
        $duration .= $array['seconds'] . ' ' . get_string('seconds',
                        'local_evaluations');
    }

    return $duration;
}

function role_install() {
    global $DB;
    $timenow = time();
    $context = get_context_instance(CONTEXT_SYSTEM);


    /// Fully setup the Elluminate Moderator role.
    if (!$mrole = $DB->get_record('role',
            array('shortname' => 'evaluation_invigilator'))) {

        if ($rid = create_role(get_string('evaluationinvigilator',
                        'local_evaluations'), 'evaluation_invigilator',
                get_string('evaluationinvigilatordescription',
                        'local_evaluations'))) {

            $mrole = $DB->get_record('role', array('id' => $rid));
            assign_capability('local/evaluations:invigilator', CAP_ALLOW,
                    $mrole->id, $context->id);

            //Only assignable at course level
            set_role_contextlevels($mrole->id, array(CONTEXT_COURSE));
        } else {
            $mrole = $DB->get_record('role',
                    array('shortname' => 'evaluation_invigilator'));
            set_role_contextlevels($mrole->id, array(CONTEXT_COURSE));
        }
    }
}

/**
 * Who has this capability in this context?
 *
 * This can be a very expensive call - use sparingly and keep
 * the results if you are going to need them again soon.
 *
 * Note if $fields is empty this function attempts to get u.*
 * which can get rather large - and has a serious perf impact
 * on some DBs.
 *
 * @param object $context
 * @param string|array $capability - capability name(s)
 * @param string $fields - fields to be pulled. The user table is aliased to 'u'. u.id MUST be included.
 * @param string $sort - the sort order. Default is lastaccess time.
 * @param mixed $limitfrom - number of records to skip (offset)
 * @param mixed $limitnum - number of records to fetch
 * @param string|array $groups - single group or array of groups - only return
 *               users who are in one of these group(s).
 * @param string|array $exceptions - list of users to exclude, comma separated or array
 * @param bool $doanything_ignored not used any more, admin accounts are never returned
 * @param bool $view_ignored - use get_enrolled_sql() instead
 * @param bool $useviewallgroups if $groups is set the return users who
 *               have capability both $capability and moodle/site:accessallgroups
 *               in this context, as well as users who have $capability and who are
 *               in $groups.
 * @return mixed
 */
function get_users_by_capability_search($context, $capability, $fields = '',
        $sort = '', $limitfrom = '', $limitnum = '', $groups = '',
        $exceptions = '', $search_string = '', $search_params = array(),
        $useviewallgroups = false) {
    global $CFG, $DB;

    if (empty($context->id)) {
        throw new coding_exception('Invalid context specified');
    }

    $defaultuserroleid = isset($CFG->defaultuserroleid) ? $CFG->defaultuserroleid : 0;
    $defaultfrontpageroleid = isset($CFG->defaultfrontpageroleid) ? $CFG->defaultfrontpageroleid : 0;

    $ctxids = trim($context->path, '/');
    $ctxids = str_replace('/', ',', $ctxids);

    // Context is the frontpage
    $iscoursepage = false; // coursepage other than fp
    $isfrontpage = false;
    if ($context->contextlevel == CONTEXT_COURSE) {
        if ($context->instanceid == SITEID) {
            $isfrontpage = true;
        } else {
            $iscoursepage = true;
        }
    }
    $isfrontpage = ($isfrontpage || is_inside_frontpage($context));

    $caps = (array) $capability;

    // construct list of context paths bottom-->top
    list($contextids, $paths) = get_context_info_list($context);

    // we need to find out all roles that have these capabilities either in definition or in overrides
    $defs = array();
    list($incontexts, $params) = $DB->get_in_or_equal($contextids,
            SQL_PARAMS_NAMED, 'con');
    list($incaps, $params2) = $DB->get_in_or_equal($caps, SQL_PARAMS_NAMED,
            'cap');
    $params = array_merge($params, $params2);
    $sql = "SELECT rc.id, rc.roleid, rc.permission, rc.capability, ctx.path
              FROM {role_capabilities} rc
              JOIN {context} ctx on rc.contextid = ctx.id
             WHERE rc.contextid $incontexts AND rc.capability $incaps";

    $rcs = $DB->get_records_sql($sql, $params);
    foreach ($rcs as $rc) {
        $defs[$rc->capability][$rc->path][$rc->roleid] = $rc->permission;
    }

    // go through the permissions bottom-->top direction to evaluate the current permission,
    // first one wins (prohibit is an exception that always wins)
    $access = array();
    foreach ($caps as $cap) {
        foreach ($paths as $path) {
            if (empty($defs[$cap][$path])) {
                continue;
            }
            foreach ($defs[$cap][$path] as $roleid => $perm) {
                if ($perm == CAP_PROHIBIT) {
                    $access[$cap][$roleid] = CAP_PROHIBIT;
                    continue;
                }
                if (!isset($access[$cap][$roleid])) {
                    $access[$cap][$roleid] = (int) $perm;
                }
            }
        }
    }

    // make lists of roles that are needed and prohibited in this context
    $needed = array(); // one of these is enough
    $prohibited = array(); // must not have any of these
    foreach ($caps as $cap) {
        if (empty($access[$cap])) {
            continue;
        }
        foreach ($access[$cap] as $roleid => $perm) {
            if ($perm == CAP_PROHIBIT) {
                unset($needed[$cap][$roleid]);
                $prohibited[$cap][$roleid] = true;
            } else if ($perm == CAP_ALLOW and empty($prohibited[$cap][$roleid])) {
                $needed[$cap][$roleid] = true;
            }
        }
        if (empty($needed[$cap]) or !empty($prohibited[$cap][$defaultuserroleid])) {
            // easy, nobody has the permission
            unset($needed[$cap]);
            unset($prohibited[$cap]);
        } else if ($isfrontpage and !empty($prohibited[$cap][$defaultfrontpageroleid])) {
            // everybody is disqualified on the frontapge
            unset($needed[$cap]);
            unset($prohibited[$cap]);
        }
        if (empty($prohibited[$cap])) {
            unset($prohibited[$cap]);
        }
    }

    if (empty($needed)) {
        // there can not be anybody if no roles match this request
        return array();
    }

    if (empty($prohibited)) {
        // we can compact the needed roles
        $n = array();
        foreach ($needed as $cap) {
            foreach ($cap as $roleid => $unused) {
                $n[$roleid] = true;
            }
        }
        $needed = array('any' => $n);
        unset($n);
    }

    /// ***** Set up default fields ******
    if (empty($fields)) {
        if ($iscoursepage) {
            $fields = 'u.*, ul.timeaccess AS lastaccess';
        } else {
            $fields = 'u.*';
        }
    } else {
        if (debugging('', DEBUG_DEVELOPER) && strpos($fields, 'u.*') === false && strpos($fields,
                        'u.id') === false) {
            debugging('u.id must be included in the list of fields passed to get_users_by_capability().',
                    DEBUG_DEVELOPER);
        }
    }

    /// Set up default sort
    if (empty($sort)) { // default to course lastaccess or just lastaccess
        if ($iscoursepage) {
            $sort = 'ul.timeaccess';
        } else {
            $sort = 'u.lastaccess';
        }
    }
    $sortby = "ORDER BY $sort";

    // Prepare query clauses
    $wherecond = array();
    $params = array();
    $joins = array();

    // User lastaccess JOIN
    if ((strpos($sort, 'ul.timeaccess') === false) and (strpos($fields,
                    'ul.timeaccess') === false)) {
        // user_lastaccess is not required MDL-13810
    } else {
        if ($iscoursepage) {
            $joins[] = "LEFT OUTER JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = {$context->instanceid})";
        } else {
            throw new coding_exception('Invalid sort in get_users_by_capability(), ul.timeaccess allowed only for course contexts.');
        }
    }

    /// We never return deleted users or guest account.



    $wherecond[] = "$search_string";


    $params = array_merge($params, $search_params);

    $params['guestid'] = $CFG->siteguest;

    /// Groups
    if ($groups) {
        $groups = (array) $groups;
        list($grouptest, $grpparams) = $DB->get_in_or_equal($groups,
                SQL_PARAMS_NAMED, 'grp');
        $grouptest = "u.id IN (SELECT userid FROM {groups_members} gm WHERE gm.groupid $grouptest)";
        $params = array_merge($params, $grpparams);

        if ($useviewallgroups) {
            $viewallgroupsusers = get_users_by_capability($context,
                    'moodle/site:accessallgroups', 'u.id, u.id', '', '', '', '',
                    $exceptions);
            $wherecond[] = "($grouptest OR u.id IN (" . implode(',',
                            array_keys($viewallgroupsusers)) . '))';
        } else {
            $wherecond[] = "($grouptest)";
        }
    }

    /// User exceptions
    if (!empty($exceptions)) {

        $exceptions = (array) $exceptions;
        list($exsql, $exparams) = $DB->get_in_or_equal($exceptions,
                SQL_PARAMS_NAMED, 'exc', false);
        $params = array_merge($params, $exparams);
        $wherecond[] = "u.id $exsql";
    }

    // now add the needed and prohibited roles conditions as joins
    if (!empty($needed['any'])) {
        // simple case - there are no prohibits involved
        if (!empty($needed['any'][$defaultuserroleid]) or ($isfrontpage and !empty($needed['any'][$defaultfrontpageroleid]))) {
            // everybody
        } else {
            $joins[] = "JOIN (SELECT DISTINCT userid
                                FROM {role_assignments}
                               WHERE contextid IN ($ctxids)
                                     AND roleid IN (" . implode(',',
                            array_keys($needed['any'])) . ")
                             ) ra ON ra.userid = u.id";
        }
    } else {
        $unions = array();
        $everybody = false;
        foreach ($needed as $cap => $unused) {
            if (empty($prohibited[$cap])) {
                if (!empty($needed[$cap][$defaultuserroleid]) or ($isfrontpage and !empty($needed[$cap][$defaultfrontpageroleid]))) {
                    $everybody = true;
                    break;
                } else {
                    $unions[] = "SELECT userid
                                   FROM {role_assignments}
                                  WHERE contextid IN ($ctxids)
                                        AND roleid IN (" . implode(',',
                                    array_keys($needed[$cap])) . ")";
                }
            } else {
                if (!empty($prohibited[$cap][$defaultuserroleid]) or ($isfrontpage and !empty($prohibited[$cap][$defaultfrontpageroleid]))) {
                    // nobody can have this cap because it is prevented in default roles
                    continue;
                } else if (!empty($needed[$cap][$defaultuserroleid]) or ($isfrontpage and !empty($needed[$cap][$defaultfrontpageroleid]))) {
                    // everybody except the prohibitted - hiding does not matter
                    $unions[] = "SELECT id AS userid
                                   FROM {user}
                                  WHERE id NOT IN (SELECT userid
                                                     FROM {role_assignments}
                                                    WHERE contextid IN ($ctxids)
                                                          AND roleid IN (" . implode(',',
                                    array_keys($prohibited[$cap])) . "))";
                } else {
                    $unions[] = "SELECT userid
                                   FROM {role_assignments}
                                  WHERE contextid IN ($ctxids)
                                        AND roleid IN (" . implode(',',
                                    array_keys($needed[$cap])) . ")
                                        AND roleid NOT IN (" . implode(',',
                                    array_keys($prohibited[$cap])) . ")";
                }
            }
        }
        if (!$everybody) {
            if ($unions) {
                $joins[] = "JOIN (SELECT DISTINCT userid FROM ( " . implode(' UNION ',
                                $unions) . " ) us) ra ON ra.userid = u.id";
            } else {
                // only prohibits found - nobody can be matched
                $wherecond[] = "1 = 2";
            }
        }
    }

    // Collect WHERE conditions and needed joins
    $where = implode(' AND ', $wherecond);
    if ($where !== '') {
        $where = 'WHERE ' . $where;
    }
    $joins = implode("\n", $joins);

    /// Ok, let's get the users!
    $sql = "SELECT $fields
              FROM {user} u
            $joins
            $where
          ORDER BY $sort";

    return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
}

/**
 * Generates a list of departments that are in the system.
 * 
 * @return array    Returns an associative array with the department code as the 
 *      key and the the department name as the value.
 */
function get_departments() {
    $dept = array("AGST" => "Agricultural Studies",
        "ANTH" => "Anthropology",
        "ARKY" => "Archaeology",
        "ART" => "Art",
        "AHMS" => "Art History/Museum Studies",
        "ASCI" => "Arts and Science",
        "ASTR" => "Astronomy",
        "BCHM" => "Biochemistry",
        "BIOL" => "Biology",
        "BKFT" => "Blackfoot",
        "CAAP" => "Campus Alberta",
        "CDEV" => "Career Development",
        "CHEM" => "Chemistry",
        "CPSC" => "Computer Science",
        "DRAM" => "Drama",
        "ECON" => "Economics",
        "EDUC" => "Education",
        "ENGG" => "Engineering",
        "ENGL" => "English",
        "ENVS" => "Environmental Science",
        "FREN" => "French",
        "GEOG" => "Geography",
        "GEOL" => "Geology",
        "GERM" => "German",
        "HLSC" => "Health Sciences",
        "HIST" => "History",
        "IDST" => "Interdisciplinary Studies",
        "JPNS" => "Japanese",
        "KNES" => "Kinesiology",
        "LATI" => "Latin",
        "LBED" => "Liberal Education",
        "LBSC" => "Library Science",
        "LING" => "Linguistics",
        "LOGI" => "Logic",
        "MATH" => "Mathematics",
        "MUSI" => "Music",
        "MUSE" => "Music Ensemble Activity",
        "NAS" => "Native American Studies",
        "NEUR" => "Neuroscience",
        "NMED" => "New Media",
        "NURS" => "Nursing",
        "PHIL" => "Philosophy",
        "PHAC" => "Physical Activity",
        "PHYS" => "Physics",
        "POLI" => "Political Science",
        "PSYC" => "Psychology",
        "PUBH" => "Public Health",
        "RELS" => "Religious Studies",
        "SSCI" => "Social Sciences",
        "SOCI" => "Sociology",
        "SPAN" => "Spanish",
        "STAT" => "Statistics",
        "WMST" => "Women's Studies",
        "WRIT" => "Writing");

    return $dept;
}

/**
 * Determines if the given user is the administrator of the given department.
 * 
 * @global moodle_database $DB
 * @param String $dept The department code that you want to check. (The keys from
 *      get_departments())
 * @param stdClass $user a user object from the database.
 * @return boolean  Wehther or not the given user is an admin for the given department.
 */
function is_dept_admin($dept, $user) {
    global $DB;

    $isAdmin = $DB->get_record_select('department_administrators',
                    'userid = ' . $user->id . ' AND ' . 'department = \'' . $dept . '\'') ? true : false;

    return $isAdmin || has_capability('local/evaluations:admin',
                    get_context_instance(CONTEXT_SYSTEM));
}

?>
