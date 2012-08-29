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
 * Form for creating new evaluations or editing old ones. 
 */
require_once("$CFG->libdir/formslib.php");
require_once('classes/question.php');
require_once('classes/evaluation.php');
require_once('locallib.php');

class eval_form extends moodleform {

    private $eval_id;
    private $version = 'error';
    private $status = -5; //no status = -5
    private $dept;

    /**
     * Constructor for the evalform class
     *  
     * @param int $dept The department code.
     * @param int $eval_id The id of the evaluation that this form is representing. 
     *  Use 0 if it's a new evaluation. Defaults to 0.
     * @param int $courseid The id of the course that this evaluation falls under.
     *  This defaults to null.
     */
    function __construct($dept, $eval_id = 0, $courseid = null) {
        $this->dept = $dept;
        $this->eval_id = $eval_id;
        $this->customCourseid = $courseid;
        if ($this->eval_id > 0) {//existing eval
            $status = eval_check_status($eval_id);

            if ($status <= 0) { //error
                $this->version = 'error';
                $this->status = $status;
            } else { //proper result
                $this->status = $status;

                if ($status == EVAL_STATUS_PRESTART) {//pre-start
                    $this->version = 'full';
                } elseif ($status == EVAL_STATUS_INPROGRESS) {//inprogress
                    $this->version = 'limited';
                } elseif ($status == EVAL_STATUS_COMPLETE) {//completed
                    $this->version = 'none';
                }
            }
        } else {//New eval!
            $this->version = 'full';
        }


        parent::__construct();
    }

    /**
     * Defines the content of the form.
     */
    function definition() {


        $mform = & $this->_form; // Don't forget the underscore! 
        $mform->addElement('hidden', 'eval_status', $this->version); //status of the eval for validation checks
        //Call the function to build the rest of the report based on it's version.
        //Version is Full(Prestart), Limited(In Progress), None(Complete) or Error(Error).
        $function = 'definition_' . $this->version;
        $this->$function();
    }

    /**
     * Generate the form if it is a new form or in the prestart phases.
     * Apparently it is also called by definition_limited which occurs during the
     * In Progress phase.
     * 
     * @global moodle_database $DB
     */
    function definition_full() {
        global $CFG, $DB, $USER;

        //Make sure the user is a department administrator.
        if (!is_dept_admin($this->dept, $USER)) {
            print_error(get_string('restricted', 'local_evaluations'));
        }

        //get the form object.
        $mform = & $this->_form;

        $mform->addElement('hidden', 'dept', $this->dept);

        //General information.
        $mform->addElement('header', 'general_header',
                get_string('general', 'local_evaluations'));

        //If course id was set then make sure it stays here when form is submitted.
        if (isset($this->customCourseid)) {
            $mform->addElement('hidden', 'cid', $this->customCourseid);
        }

        //Name
        $attributes = array('size' => '30');
        $mform->addElement('text', 'eval_name',
                get_string('eval_name_c', 'local_evaluations'), $attributes);


        //Courses
        $courses = $DB->get_records_select('course',
                "fullname LIKE '$this->dept%'");

        $course_choices = array();
        foreach ($courses as $id => $course) {
            $course_choices[$id] = $course->fullname;
        }

        if ($this->version == 'limited') {
            $mform->addElement('hidden', 'eval_course_id', 0);
        } else {
            $attributes = array();
            $mform->addElement('select', 'eval_course_id',
                    get_string('course_c', 'local_evaluations'),
                    $course_choices, $attributes);
        }

        //Student email reminders
        $student_email_choices = array(get_string('no'), get_string('yes'));
        $mform->addElement('select', 'student_email_toogle',
                get_string('student_email', 'local_evaluations'),
                $student_email_choices, array());


        //Date Selectors : to->from         
        if ($this->version == 'limited') { //limited
            //Hacky, but when disabled the date_time_selector loses its value...
            $mform->addElement('date_time_selector', 'eval_time_start_display',
                    get_string('from')); //to display only
            $mform->disabledIf('eval_time_start_display', 'eval_id', 'neq', 0);
            $mform->addElement('hidden', 'eval_time_start', 0);
        } else { //full editing
            $mform->addElement('date_time_selector', 'eval_time_start',
                    get_string('from'));
        }

        $mform->addElement('date_time_selector', 'eval_time_end',
                get_string('to'));

        $mform->addElement('hidden', 'eval_id', $this->eval_id);
        $mform->addElement('hidden', 'eval_complete', 0);



        $evaluation = new evaluation($this->dept, $this->eval_id);

        $data = new stdClass();

        $loadQuestions = true;
        if ($this->version == 'limited') {
            $loadQuestions = false;
        }

        //Load question data - either exisiting questions or standard questions
        $evaluation->load_creation_form($mform, $this, $data, $loadQuestions);

        //Load the data as grabbed from load_creation_form
        $this->set_data($data);

        //Add things required by page javascript
        if (isset($data->question_x)) {
            $mform->addElement('hidden', 'num_default_q',
                    sizeof($data->question_x), 'id="num_default_q"');
        } else {
            $mform->addElement('hidden', 'num_default_q', -1,
                    'id="num_default_q"');
        }
    }

    /**
     * Generate the form if it's in the "in progres" phase. 
     */
    function definition_limited() {
        $this->definition_full();
    }

    /**
     * Generate the form if it's in the "complete" phase.
     */
    function definition_none() {
        $mform = & $this->_form; // Don't forget the underscore! 

        $output = '<h3>';
        $output .= get_string('form_restricted', 'local_evaluations');
        $output .= '</h3>';

        $mform->addElement('html', $output);
    }

    /**
     * Generate form if an error occured.
     */
    function definition_error() {
        $mform = & $this->_form; // Don't forget the underscore! 

        $output = '<h3><font color="red">';
        $output .= get_string('form_error', 'local_evaluations') . '<br>';

        if (isset($this->status)) {
            $output .= 'Error: ' . ($this->status);
        }

        $output .= '</font></h3>';

        $mform->addElement('html', $output);
    }

    /**
     * Validate the data that was entered.
     * 
     * @param array $data An assoicative array of all submitted data.
     * @param $files Not Used.
     * @return String An error string.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        //start less than or equal to end time
        if ($data['eval_time_start'] >= $data['eval_time_end']) {
            $errors['eval_time_start'] = get_string('startLEend',
                    'local_evaluations');
        }

        //end time less than current time
        if ($data['eval_time_end'] <= time()) {
            $errors['eval_time_end'] = get_string('end_LE_now',
                    'local_evaluations');
        }

        //eval already started, and tried to make eval start time later than now aka make pre-start
        if ($this->status == 2 && $data['eval_time_start'] > time()) {
            $errors['eval_time_start'] = get_string('already_started',
                    'local_evaluations');
        }

        //eval hasn't started, and tried to make eval start before now - make in progress
        if ($this->status == 1 && $data['eval_time_start'] <= time()) {
            $errors['eval_time_start'] = get_string('cannot_started',
                    'local_evaluations');
        }


        return $errors;
    }

}

?>
