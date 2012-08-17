<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of question
 *
 * @author dddurand
 */
class question {

    protected $id = 0;
    protected $question;
    protected $type;
    protected $order;
    protected $isstd; //Is standard.

    //New Message or Existing with updated data
    //$DB_load The default true means that the question is loaded from the $DB, if set to false will use provided data instead
    //If $id is zero, then it assumes its a new question
    function __construct($isstd, $id = 0, $question='', $type=null, $order=null, $DB_load=true) {
        $this->isstd = $isstd;
        
        if (!is_number($id)) {
            print_error(get_string('question_id_invalid', 'local_evaluations'));
        }

        if ($id > 0) {
            $this->verify_question_exists($id, $DB_load);
        }


        $this->id = $id;

        if (!$DB_load) {//use updated parameters
            $this->question = $question;
            $this->type = $type;
            $this->order = $order;
        }
    }

    function verify_question_exists($id) {
        global $DB;
        if (!$question = $DB->get_record('evaluation_questions', array('id' => $id))) {
            print_error(get_string('question_id_invalid', 'local_evaluations'));
        }


        $this->question = $question->question;
        $this->type = $question->type;
        $this->order = $question->question_order;
    }

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
            $DB->insert_record('evaluation_questions',$question);
            
        } else {//update existing question
            
            $DB->update_record('evaluation_questions',$question);
            
        }
    }
    
    

    function load_creation_form(&$form, $data) {
        
        $data->question_x[$this->order] = $this->question;
        $data->question_type_id[$this->order] = $this->type;
        $data->questionid_x[$this->order] = $this->id;
        $data->question_std[$this->order] = $this->isstd;
        
    }
    
    function delete($eval_id){
        global $DB;
        
        if($eval_id == 0){
            print_error(get_string('new_eval_delete_question','local_evaluation'));
        }
        
        $conditions = array('id'=>$this->id);       
       $DB->delete_records('evaluation_questions', $conditions);
    
       //update order
       $questionSet = $DB->get_records('evaluation_questions', array('evalid'=>$eval_id), 'question_order ASC');
       
       //redo order
       $i = 0;
       
       foreach($questionSet as $question){
         $updated_question = new stdClass();
         $updated_question->id = $question->id;
         $updated_question->question_order = $i;
         
         $DB->update_record('evaluation_questions', $updated_question);
           
           
           $i++;
       }
       
       
    }

        function order_swapup($eval_id){
           global $DB;  
           
        $questionPrior = $DB->get_record('evaluation_questions', array('evalid'=>$eval_id, 'question_order'=>$this->order -1));
    
        if($questionPrior == null) return;
        
        $updated_question = new stdClass();
         $updated_question->id = $this->id;
         $updated_question->question_order = $this->order-1;
         
         $DB->update_record('evaluation_questions', $updated_question);
         
         $updated_question = new stdClass();
         $updated_question->id = $questionPrior->id;
         $updated_question->question_order = $this->order;
         
         $DB->update_record('evaluation_questions', $updated_question);
        
         $this->order--;
        
    }
    
    function order_swapdown($eval_id){
        global $DB;
        $questionLater = $DB->get_record('evaluation_questions', array('evalid'=>$eval_id,'question_order'=>$this->order +1));
    
        if($questionLater == null) return;
        
        $updated_question = new stdClass();
         $updated_question->id = $this->id;
         $updated_question->question_order = $this->order+1;
         
         $DB->update_record('evaluation_questions', $updated_question);
         
         $updated_question = new stdClass();
         $updated_question->id = $questionLater->id;
         $updated_question->question_order = $this->order;
         
         $DB->update_record('evaluation_questions', $updated_question);
        
         $this->order--;
        
    }
    
    
}

?>
