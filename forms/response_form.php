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
 * 
 */
require_once("$CFG->libdir/formslib.php");
require_once('classes/evaluation.php');
require_once('locallib.php');

class response_form extends moodleform {

    private $evalid;

    function __construct($evalid) {
        $this->evalid = $evalid;
        parent::__construct();
    }

    function definition() {
        global $DB;
        $mform = & $this->_form;

        $mform->addElement('header', 'question_response_header',
                get_string('question_response_header', 'local_evaluations'));
        $mform->addElement('html',
                '<p>' . get_string('question_response_info', 'local_evaluations') . '</p>');

        $evaluation = new evaluation(null, $this->evalid);

        $mform->addElement('text', 'course_name',
                get_string('course_name', 'local_evaluations'),
                array('disabled' => 'disabled'));
        $mform->addElement('text', 'professor_name',
                get_string('professor_name', 'local_evaluations'),
                array('disabled' => 'disabled'));

        $course_context = get_context_instance(CONTEXT_COURSE,
                $evaluation->get_course());
        $teacher_info = get_role_users(3, $course_context);
        $course_info = $DB->get_record('course',
                array('id' => $evaluation->get_course()));

        $mform->setDefault('course_name', $course_info->fullname);
        
        $prof_name = get_string('none', 'local_evaluations');
        
        if(count($teacher_info) != 0) {
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
