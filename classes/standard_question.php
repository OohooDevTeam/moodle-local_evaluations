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

    function __construct($id = 0, $question = '', $type = null, $order = null, $DB_load = true, $dept = null) {
        global $DB;
        if ($id != 0) {
            $this->dept = $DB->get_record('evaluation_standard_question', array('id' => $id))->department;
        } else {
            $this->dept = $dept;
        }
        parent::__construct(true, $id, $question, $type, $order, $DB_load);
    }

    //Save as a standard question
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

    function verify_question_exists($id) {
        global $DB;
        if (!$question = $DB->get_record('evaluation_standard_question', array('id' => $id))) {
            print_error(get_string('question_id_invalid', 'local_evaluations') . ' ' . $id);
        }


        $this->question = $question->question;
        $this->type = $question->type;
        $this->order = $question->question_order;
    }

    function delete($garbage) {
        global $DB;

        $conditions = array('id' => $this->id);
        $DB->delete_records('evaluation_standard_question', $conditions);


        $questionSet = $DB->get_records_select('evaluation_standard_question', 'department = \'' . $this->dept .'\'', null, 'question_order ASC');

        //redo order
        $i = 0;

        foreach ($questionSet as $question) {
            $updated_question = new stdClass();
            $updated_question->id = $question->id;
            $updated_question->question_order = $i;

            $DB->update_record('evaluation_standard_question', $updated_question);


            $i++;
        }
    }

    function order_swapup($garbage) {
        global $DB;

        $questionPrior = $DB->get_record_select('evaluation_standard_question',
                'question_order = ' . ($this->order - 1) . ' AND department = \'' . $this->dept . '\'');

        if ($questionPrior == null)
            return;

        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order - 1;

        $DB->update_record('evaluation_standard_question', $updated_question);

        $updated_question = new stdClass();
        $updated_question->id = $questionPrior->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_standard_question', $updated_question);

        $this->order--;
    }

    function order_swapdown($garbage) {
        global $DB;
        $questionLater = $DB->get_record_select('evaluation_standard_question', 'question_order = ' . ($this->order + 1) . ' AND department = \'' . $this->dept . '\'');

        if ($questionLater == null)
            return;

        $updated_question = new stdClass();
        $updated_question->id = $this->id;
        $updated_question->question_order = $this->order + 1;

        $DB->update_record('evaluation_standard_question', $updated_question);

        $updated_question = new stdClass();
        $updated_question->id = $questionLater->id;
        $updated_question->question_order = $this->order;

        $DB->update_record('evaluation_standard_question', $updated_question);

        $this->order--;
    }

}

?>
