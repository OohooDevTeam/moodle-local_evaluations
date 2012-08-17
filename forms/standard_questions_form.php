<?php

require_once("$CFG->libdir/formslib.php");
require_once('classes/standard_question.php');
require_once('classes/standard_question_set.php');
require_once('locallib.php');

class standard_questions_form extends moodleform {

    private $dept;

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
