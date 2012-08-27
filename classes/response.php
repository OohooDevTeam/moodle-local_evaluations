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
 * This class handles everything related to creating new responses.
 */
class response {
    private $id; //The database id of the response.
    private $question_id; //The id of the question that this response is responding to.
    private $response; //The value'd response if it exists. (4_excellent, 5_superior, 4_rate_wc)
    private $user_id;  //The user who made this response.
    private $comment; //The text response to a question if it exists. (comment, years)
    
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
    
    /**
     * Save new responses to the database. If the response has an id other than
     * 0 then this method will fail because we should never allow a response
     * to be edited.
     * 
     * @global moodle_database $DB
     */
    function save(){
        global $DB;
        
        if($this->id != 0){
            return; //WE SHOULD NEVER ALLOW A RESPONSE TO BE EDITED
        }
        
        //Save the new response to the database.
        $response = new stdClass();
        $response->question_id = $this->question_id;
        $response->response = $this->response;
        $response->user_id = $this->user_id;
        $response->question_comment = $this->comment;
        
        $DB->insert_record('evaluation_response',$response);
        
    }
    
    /**
     * Load contents of this response from the database.
     * 
     * @global moodle_database $DB
     */
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
