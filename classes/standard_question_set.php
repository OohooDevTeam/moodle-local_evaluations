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
 * Description of standard_question_set
 */
require_once('standard_question.php');

class standard_question_set {

    private $questionSet = array();
    private $dept;

    //if empty array then get questionset from database
    //otherwise load from array
    function __construct($dept, $questionSet = array()) {
        $this->dept = $dept;
        $this->loadQuestionSet($questionSet);
    }

    function loadQuestionSet($questionSet) {
        global $DB;
        $DB_load = false;


        if (empty($questionSet)) { //load from database
            if (!$questionSet = $DB->get_records_select('evaluation_standard_question', 'department = \'' . $this->dept . "'", null , 'question_order ASC')) {
                $questionSet = array();
            }


            $DB_load = true;
        }

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

        $form->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 1);



        foreach ($this->questionSet as $question) {
            $question->load_creation_form($form, $data);
        }



        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('complete', 'local_evaluations'));
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
