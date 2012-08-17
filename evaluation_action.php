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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG;

require_once('locallib.php');

$action = required_param('action',PARAM_TEXT);
$eval_id = required_param('eval_id',PARAM_INT);
$dept = required_param('dept', PARAM_TEXT);
global $CFG;

if(!$eval = $DB->get_record('evaluations',array('id'=>$eval_id))){
    print_error(get_string('eval_id_invalid','local_evaluations'));
}

$context = get_context_instance(CONTEXT_COURSE, $eval->course);

if (!has_capability('local/evaluations:instructor', $context) &&
        !is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

switch($action){
 case 'delete':
     delete_eval($eval_id);
     break;
 
 case 'force_start':
     force_start_eval($eval_id);
     break;
 
 case 'force_complete':
     force_complete_eval($eval_id);
     break;
    
}

redirect($CFG->wwwroot.'/local/evaluations/evaluations.php');



?>
