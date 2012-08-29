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
 * Form for creating standard questions for a department.
 */
require_once("$CFG->libdir/formslib.php");
require_once('classes/standard_question.php');
require_once('classes/standard_question_set.php');
require_once('locallib.php');

class standard_questions_form extends moodleform {

    private $dept;
    
    /**
     * Constructor
     * @param String $dept A department code.
     */
    function __construct($dept) {
        $this->dept = $dept;
        parent::__construct();
    }

    function definition() {
        $mform = & $this->_form;

        $mform->addElement('header', 'standard_question_header', get_string('nav_st_qe', 'local_evaluations'));
        $mform->addElement('html', '<p>' . get_string('standard_questions_info', 'local_evaluations') . '</p>');
        $mform->addElement('hidden', 'dept', $this->dept);
        $questionSet = new standard_question_set($this->dept);

        $data = new stdClass();


        //Load question data - either exisiting questions or standard questions
        $questionSet->load_creation_form($mform, $this, $data);


        $this->set_data($data);
    }

}

?>
