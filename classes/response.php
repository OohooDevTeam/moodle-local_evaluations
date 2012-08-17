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
 * Description of response
 */
class response {
    private $id;
    private $question_id;
    private $response;
    private $user_id;
    private $comment;
    
    function __construct($id = 0, $question_id = null, $response= null, $user_id = null, $comment = '') {
        
        $this->id = $id;
        
        if ($id > 0) { // DB LOAD
            $this->db_load();
        } else { //PARAM LOAD
            $this->question_id = $question_id;
            $this->response = $response;
            $this->user_id = $user_id;
            $this->comment = $comment;
        }
    }
    
    function save(){
        global $DB;
        
        if($this->id != 0){
            return; //WE SHOULD NEVER ALLOW A RESPONSE TO BE EDITED
        }
        
        $response = new stdClass();
        $response->question_id = $this->question_id;
        $response->response = $this->response;
        $response->user_id = $this->user_id;
        $response->question_comment = $this->comment;
        
        $DB->insert_record('evaluation_response',$response);
        
    }
    
    function db_load(){
        global $DB;
        
        //explicitly not loading user_id
        //SHOULD never be attached to a response except on the inital save(anonymous evaluations are assumed)
        $response = get_record('evaluation_response',array('id'=>$this->id),'id, question_id, question_comment');
        
        if(!$response){
            print_error(get_string('invalid_responseid','local_evaluations'));
        }
        
        $this->question_id = $response->question_id;
        $this->response = $response->response;
        $this->user_id = $response->user_id;
        $this->comment = $response->comment;
        
        
    }

}

?>
