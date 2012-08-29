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
 * Form for creating responding to the questions in an evaluation.
 */
require_once("$CFG->libdir/formslib.php");
require_once('classes/evaluation.php');
require_once('locallib.php');

class response_form extends moodleform {

    private $evalid;

    /**
     * Constructor
     * 
     * @param int $evalid The id of the evaluation that the user will be 
     *  responding to.
     */
    function __construct($evalid) {
        $this->evalid = $evalid;
        parent::__construct();
    }

    /**
     * The definition of the form.
     * @global moodle_database $DB
     */
    function definition() {
        global $DB;
        $mform = & $this->_form;

        $mform->addElement('header', 'question_response_header',
                get_string('question_response_header', 'local_evaluations'));


        //Get an evaluation object for the given evaluation.
        $evaluation = new evaluation(null, $this->evalid);

        //Add the course name.
        $mform->addElement('text', 'course_name',
                get_string('course_name', 'local_evaluations'),
                array('disabled' => 'disabled'));
        $mform->addElement('text', 'professor_name',
                get_string('professor_name', 'local_evaluations'),
                array('disabled' => 'disabled'));

        //Get and add course/teacher information.
        $course_context = get_context_instance(CONTEXT_COURSE,
                $evaluation->get_course());
        $teacher_info = get_role_users(3, $course_context);

        //Add Course name
        $course_info = $DB->get_record('course',
                array('id' => $evaluation->get_course()));
        $mform->setDefault('course_name', $course_info->fullname);

        //Add teacher name
        $prof_name = get_string('none', 'local_evaluations');
        if (count($teacher_info) != 0) {
            //If the teacher exists change name from none to the teachers name.
            $first = array_shift($teacher_info);
            $prof_name = $first->firstname . ' ' . $first->lastname;
        }
        $mform->setDefault('professor_name', $prof_name);

        $data = new stdClass();


        //Load form to be used for course evaluation
        $evaluation->load_display_form($mform, $this, $data);


        $this->set_data($data);
    }

}

?>
