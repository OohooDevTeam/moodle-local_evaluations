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
 * This class handles everything related to creating and updating questions.
 */
class question {

    protected $id = 0;  //The id of the question in the database.
    protected $question; //The text for the question 
    protected $type; //The question type
    protected $order; //The order in which the question will be displayed.
    protected $isstd; //Is standard.

    //New Message or Existing with updated data
    //$DB_load The default true means that the question is loaded from the $DB, if set to false will use provided data instead
    /**
     * Create a new question or update an old one.
     * 
     * @param boolean $isstd    Is this a standard question
     * @param int $id           The id of the question if it exists. Otherwise 0.
     * @param string $question  The text showing the question that's being asked.
     * @param int $type         The database question type id for the typoe of question
     *                          that this represents.
     * @param int $order        The order in which this question will show up in
     *                          the list
     * @param boolean $DB_load  The default true means that the question is loaded 
     *                          from the $DB, if set to false will use provided 
     *                          data instead
     */

    function __construct($isstd, $id = 0, $question = '', $type = null,
            $order = null, $DB_load = true) {
        $this->isstd = $isstd;

        //Check to make sure that the id is a number.
        if (!is_number($id)) {
            print_error(get_string('question_id_invalid', 'local_evaluations'));
        }

        //If it is not a new question then make sure the question exists.
        if ($id > 0) {
            $this->verify_question_exists($id);
        }


        //Set the id.
        $this->id = $id;


        if (!$DB_load) {//use updated parameters
            $this->question = $question;
            $this->type = $type;
            $this->order = $order;
        }
    }

    /**
     * Verify that the question with the given id exists in the database.
     * 
     * @global moodle_database $DB
     * @param  $id
     */
    function verify_question_exists($id) {
        global $DB;
        if (!$question = $DB->get_record('evaluation_questions',
                array('id' => $id))) {
            print_error(get_string('question_id_invalid', 'local_evaluations'));
        }

        //Set data if it does exist. -- Will be overridden later if DB_LOAD is not set.
        $this->question = $question->question;
        $this->type = $question->type;
        $this->order = $question->question_order;
    }

    /**
     * Save this question to the evaluation with the given id.
     * 
     * @global moodle_database $DB
     * @param int $evalid The evaluation that this needs to be saved to.
     */
    function save($evalid) {
        global $DB;

        $question = new stdClass();
        $question->id = $this->id;
        $question->evalid = $evalid;
        $question->question = $this->question;
        $question->type = $this->type;
        $question->question_order = $this->order;
        $question->isstd = $this->isstd;

        if ($this->id == 0) { //new question
            unset($question->id);
            $this->id = $DB->insert_record('evaluation_questions', $question);
        } else {//update existing question
            $DB->update_record('evaluation_questions', $question);
        }
    }

    /**
     * Load data for this evaluation into the creation form elements.
     * 
     * @param moodleform $form
     * @param $data
     */
    function load_creation_form(&$form, $data) {

        $data->question_x[$this->order] = $this->question;
        $data->question_type_id[$this->order] = $this->type;
        $data->questionid_x[$this->order] = $this->id;
        $data->question_std[$this->order] = $this->isstd;
    }

    /**
     * Delete this question in the evaluation with the given id. 
     * 
     * Note: There is only a single instance of this question in the database. 
     * There is no point of grabbing the eval_id because it's easy to grab later.
     * However this is the way it was done... 
     *
     * @global moodle_database $DB
     * @param int $eval_id
     */
    function delete($eval_id) {
        global $DB;

        //If the evaluation is new then it can't be deleted. (It doesnt exist yet).
        if ($eval_id == 0) {
            print_error(get_string('new_eval_delete_question',
                            'local_evaluation'));
        }

        //Remove the record for this question.
        $DB->delete_records('evaluation_questions', array('id' => $this->id));

        //update order based on the given evaluation id because we are too lazy to get
        //the evaluation id from the database.
        $questionSet = $DB->get_records('evaluation_questions',
                array('evalid' => $eval_id), 'question_order ASC');

        //Reorder the questions in this evaluation.
        $i = 0;

        foreach ($questionSet as $question) {
            $updated_question = new stdClass();
            $updated_question->id = $question->id;
            $updated_question->question_order = $i;

            $DB->update_record('evaluation_questions', $updated_question);

            $i++;
        }
    }

    /**
     * Move this question up one space in the evaluation.
     * 
     * @global moodle_database $DB
     * @param int $eval_id
     */
    function order_swapup($eval_id) {
        global $DB;

        //Get the question that is above this one.
        $questionPrior = $DB->get_record('evaluation_questions',
                array('evalid' => $eval_id, 'question_order' => $this->order - 1));

        //If there is none then we can't swap this one with the one above it so 
        //exit.
        if ($questionPrior == null) {
            return;
        }

        //Make this question show up one step earlier.
        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order - 1;

        $DB->update_record('evaluation_questions', $updated_question);

        //Make the previous question show up one step later.
        $updated_question = new stdClass();
        $updated_question->id = $questionPrior->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_questions', $updated_question);

        //update the order of this question.
        $this->order--;
    }

    /**
     * Move this question up down space in the evaluation.
     * 
     * @global moodle_database $DB
     * @param int $eval_id
     */
    function order_swapdown($eval_id) {
        global $DB;

        //Get the question that is below this one.
        $questionLater = $DB->get_record('evaluation_questions',
                array('evalid' => $eval_id, 'question_order' => $this->order + 1));

        //If there is none then we can't swap this one with the one below it so 
        //exit.
        if ($questionLater == null)
            return;

        //Make this question show up one step later.
        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order + 1;

        $DB->update_record('evaluation_questions', $updated_question);

        //Make this question show up one step earlier.
        $updated_question = new stdClass();
        $updated_question->id = $questionLater->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_questions', $updated_question);

        //update the order of this question.
        $this->order--;
    }

    function get_id() {
        return $this->id;
    }

    function get_order() {
        return $this->order;
    }

    function get_question() {
        return $this->question;
    }

}

?>
