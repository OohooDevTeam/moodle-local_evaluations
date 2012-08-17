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

require_once("$CFG->dirroot/user/selector/lib.php");
require_once("$CFG->dirroot/local/evaluations/locallib.php");
class invig_user_selector extends user_selector_base {

    private $course;
    private $stored;

    function __construct($name, $options) {
        $this->course = $options['course'];
        $this->stored = $options['stored'];
        parent::__construct($name, $options);
        $this->set_rows($options['rows']);
        $this->set_multiselect(true);
        $this->preserveselected = true;

    }

    function find_users($search) {
        global $DB;
//'local/evaluations:invigilator'
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $course = 4;
        $context = get_context_instance(CONTEXT_COURSE, $this->course);

        $output = array();
        
        if(!empty($this->stored)){
        $sql = "SELECT u.* FROM {user} u WHERE u.id IN ($this->stored)";
        $users = $DB->get_records_sql($sql);
        $output[get_string('current_ingiv', 'local_evaluations')] = $users;
        }
       
        
        $users = get_users_by_capability_search($context, 'local/evaluations:invigilator', $fields = '', $sort = 'u.lastname', $limitfrom = '', $limitnum = '', $groups = '', $this->exclude, $wherecondition, $params, $useviewallgroups = false);      
        
        $output[get_string('matched_ingiv_users', 'local_evaluations')] = $users;
                
        return $output;
    }

    function get_options() {
        $options = parent::get_options();
        $options['file'] = '/local/evaluations/invig_user_selector.php';
         $options['course'] = $this->course;
         $options['rows'] = $this->get_rows();
         $options['stored'] = $this->stored;
        return $options;
    }

}

