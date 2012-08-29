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
 * This class defines the basic actions of standard questions in this plugin.
 * 
 * Standard questions are questions that appear on all evaluations within a
 * department.
 * 
 */
require_once('question.php');

class standard_question extends question {

    private $dept;

    /**
     * Constructor for the standard question class.
     * 
     * @global moodle_database $DB
     * @param int $id           The id of the question. 0 if it is a new question. This 
     *                          defaults to 0.
     * @param String $question  The text for the question, this defaults to empty.
     * @param int $type         The database question type id for the typoe of question
     *                          that this represents.
     * @param int $order        The order in which this question will show up in
     *                          the list
     * @param boolean $DB_load  The default true means that the question is loaded 
     *                          from the $DB, if set to false will use provided 
     *                          data instead
     * @param String $dept      A department code.
     */
    function __construct($id = 0, $question = '', $type = null, $order = null,
            $DB_load = true, $dept = null) {
        global $DB;
        if ($id != 0) {
            $this->dept = $DB->get_record('evaluation_standard_question',
                            array('id' => $id))->department;
        } else {
            $this->dept = $dept;
        }
        parent::__construct(true, $id, $question, $type, $order, $DB_load);
    }

    /**
     * Save the standard question to the database.
     * 
     * @global moodle_database $DB
     */
    function save() {
        global $DB;

        $question = new stdClass();
        $question->id = $this->id;
        $question->question = $this->question;
        $question->type = $this->type;
        $question->question_order = $this->order;
        $question->department = $this->dept;

        if ($this->id == 0) { //new question
            unset($question->id);

            $DB->insert_record('evaluation_standard_question', $question);
        } else {//update existing question
            $DB->update_record('evaluation_standard_question', $question);
        }
    }

    /**
     * Verify that a question with the given id exists.
     * @global moodle_database $DB
     * @param int $id   The question id.
     */
    function verify_question_exists($id) {
        global $DB;
        if (!$question = $DB->get_record('evaluation_standard_question',
                array('id' => $id))) {
            print_error(get_string('question_id_invalid', 'local_evaluations') . ' ' . $id);
        }


        $this->question = $question->question;
        $this->type = $question->type;
        $this->order = $question->question_order;
    }

    /**
     * Delete this question from the database.
     * @global moodle_database $DB
     */
    function delete() {
        global $DB;

        $DB->delete_records('evaluation_standard_question',
                array('id' => $this->id));

        $questionSet = $DB->get_records_select('evaluation_standard_question',
                'department = \'' . $this->dept . '\'', null,
                'question_order ASC');

        //Reorder the other questions. (Since there is now once missing all the
        //questions that came after will be slightly off.
        $i = 0;

        foreach ($questionSet as $question) {
            $updated_question = new stdClass();
            $updated_question->id = $question->id;
            $updated_question->question_order = $i;

            $DB->update_record('evaluation_standard_question', $updated_question);


            $i++;
        }
    }

    /**
     * Move this question up one space in the evaluation.
     * 
     * @global moodle_database $DB
     */
    function order_swapup() {
        global $DB;

        //Get the question that is above this one.
        $questionPrior = $DB->get_record_select('evaluation_standard_question',
                'question_order = ' . ($this->order - 1) . ' AND department = \'' . $this->dept . '\'');

        //If there is none then we can't swap this one with the one above it so 
        //exit.
        if ($questionPrior == null) {
            return;
        }

        //Make this question show up one step earlier.
        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order - 1;

        $DB->update_record('evaluation_standard_question', $updated_question);

        //Make the previous question show up one step later.
        $updated_question = new stdClass();
        $updated_question->id = $questionPrior->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_standard_question', $updated_question);

        //update the order of this question.
        $this->order--;
    }

    /**
     * Move this question up down space in the evaluation.
     * 
     * @global moodle_database $DB
     */
    function order_swapdown() {
        global $DB;

        //Get the question that is below this one.
        $questionLater = $DB->get_record_select('evaluation_standard_question',
                'question_order = ' . ($this->order + 1) . ' AND department = \'' . $this->dept . '\'');

        //If there is none then we can't swap this one with the one below it so 
        //exit.
        if ($questionLater == null) {
            return;
        }

        //Make this question show up one step later.
        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order + 1;

        $DB->update_record('evaluation_standard_question', $updated_question);

        //Make this question show up one step earlier.
        $updated_question = new stdClass();
        $updated_question->id = $questionLater->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_standard_question', $updated_question);

        //update the order of this question.
        $this->order--;
    }

}

?>
