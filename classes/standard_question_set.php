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
 * This class represents a set of standard questions. (Each department would have
 * a standard queastion set.)
 */
require_once('standard_question.php');

class standard_question_set {

    private $questionSet = array();
    private $dept;

    /**
     * Builds a question set.
     * 
     * @param String $dept  A department code for the department this set is in.
     * @param stdClass[] $questionSet An array of questions.
     */
    function __construct($dept, $questionSet = array()) {
        global $DB;

        $this->dept = $dept;

        //Assume we don't need to load from database
        $DB_load = false;

        //If the question set is empty then we need to load the data from the database.
        if (empty($questionSet)) {
            //Get all std questions from the database for this department.
            if (!$questionSet = $DB->get_records_select('evaluation_standard_question',
                    'department = \'' . $this->dept . "'", null,
                    'question_order ASC')) {
                $questionSet = array();
            }

            //Inform the next step that we will need to load each question from the database.
            $DB_load = true;
        }

        //Load the questions passed in or grabbed into this class.
        foreach ($questionSet as $order => $question) {

            if ($question->id == 0 && $question->question == '')
                continue;

            $this->questionSet[$order] = new standard_question($question->id, $question->question, $question->type, $question->question_order, $DB_load, $this->dept);
        }
    }

 
    function load_creation_form(&$mform, $form, $data) {
        global $DB;

        $repeatarray = questionCreation_mform($mform);

        $repeatno = count($this->questionSet);
        $repeatno += 1;

        $repeateloptions = array();

        $form->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                'option_repeats', 'option_add_fields', 1);



        foreach ($this->questionSet as $question) {
            $question->load_creation_form($form, $data);
        }



        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                        get_string('complete', 'local_evaluations'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function save() {
        foreach ($this->questionSet as $question) {
            $question->save();
        }
    }

}

?>
