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

// Redirects to correct archives home page
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG;

require_once('locallib.php');
require_once('invig_user_selector.php');

$eval_id = required_param('eval_id', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);


$PAGE->set_url($CFG->wwwroot.'/local/evaluations/evaluation.php');

$navlinks = array(
        array(
                'name'=>get_string('nav_ev_mn','local_evaluations'),
                'link'=>$CFG->wwwroot.'/local/evaluations/index.php',
                'type'=>'misc'
        ),
        array(
                'name'=>get_string('nav_ev_course','local_evaluations'),
                'link'=>$CFG->wwwroot.'/local/evaluations/evaluations.php',
                'type'=>'misc'
        ),

        array(
                'name'=>get_string('invigilators','local_evaluations'),
                'link'=>'',
                'type'=>'misc'
        )

);

$nav = build_navigation($navlinks);

$eval_db = $DB->get_record('evaluations', array('id' => $eval_id));
if ($eval_db) {
    $context = get_context_instance(CONTEXT_COURSE, $eval_db->course);
    //Check user has access
    if (!has_capability('local/evaluations:instructor', $context)) {
        print_error(get_string('restricted', 'local_evaluations'));
    }
    
    //check that the eval is the correct type
    if(trim($eval_db->type)!='invig'){
      print_error(get_string('invalid_eval_type', 'local_evaluations'));  
    }
    
    //check eval is still in pre-start phase - FULL EXITING
    //otherwise - VIEW ONLY
    if(eval_check_status($eval_db)== 1){
      $editable = true;  
    } else {
       $editable = false;  
    }
    
    $PAGE->set_context($context);
    $eval_name = $eval_db->name;
    require_login($eval_db->course);
} else {
print_error(get_string('invalid_evaluation','local_evaluations'));
}

//invigilators

 $PAGE->requires->css('/local/evaluations/style.css'); 
$PAGE->set_title(get_string('invigilators','local_evaluations'));
$PAGE->set_heading($eval_name);

//User Selector
$options = array('course'=>$eval_db->course,'rows'=>10,'stored'=>'');
$userselector = new invig_user_selector('test',$options);


//DEAL WITH SUBMISSION OF FORM
if($editable){
switch($action){
    case 'add':
    $users = $userselector->get_selected_users();
    
   foreach($users as $user){
    add_invigilator($eval_id,$user->id);   
   }     
    
        
    break;
    
    case 'delete':
    $invig_id = optional_param('invig_id',0,PARAM_INT);
     
        if($invig_id == 0){
         break;
     }  
     
     remove_invigilator($invig_id);
        
    break;

default:break;

}
}

//Display Form
echo $OUTPUT->header();    

$sql = 'SELECT ei.id, u.id as uid, u.firstname, u.lastname, u.email
    FROM {evaluations_invigilators} ei
     JOIN {user} u ON u.id = ei.userid
    WHERE ei.evalid ='.$eval_id;

$invigs = $DB->get_records_sql($sql);

//Currently Selected Invigilators
echo '<h3>'.get_string('current_ingiv','local_evaluations').'</h3>';
echo '<table>';

if(empty($invigs)){
      echo '<tr>';
     echo '<td>';
     echo get_string('none');
     echo '</td>';
     echo '<tr>';
}

$exclude = array();
$i = 1;
foreach($invigs as $invig){
    $exclude[] = $invig->uid;
    echo '<tr>';
     echo '<td>';
     echo "$i. ";
     echo '</td>';
    
    echo '<td>';
    echo "$invig->firstname $invig->lastname";
    echo '</td>';
    
    echo '<td>';
    echo '<a href="mailto:'.$invig->email.'">'.$invig->email.'</a>';
    echo '</td>';
    
    if($editable){
    echo '<td>';
    echo '<form action="?eval_id='.$eval_id.'" method="post">';
    echo '<input type="hidden" name="invig_id" value="'.$invig->id.'">';
    echo '<input type="hidden" name="action" value="delete">';
    echo '<input type="image" src="images/delete.png" value="Submit" alt="Submit">';
    echo '</form>';
    echo '</td>';
    
    echo '</tr>';
    }
    $i++;
}
echo '</table>';

if($editable){
echo '<br><form action="?eval_id='.$eval_id.'" method="post">';

//User Selector

$userselector->exclude($exclude);
$userselector->invalidate_selected_users();
$userselector = $userselector->display(true);
echo $userselector;
echo '<input type="hidden" name="action" value="add">';
echo '</br><input type="submit" value="'.get_string('add_invig','local_evaluations').'">';
echo '</form>';
}

echo $OUTPUT->footer();

