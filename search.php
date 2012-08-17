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
require_once('locallib.php');
require_once("$CFG->libdir/datalib.php");
//require_once("$CFG->libdir/formslib.php");
global $CFG, $DB;

$searchstring = optional_param('search', null, PARAM_RAW);
$courseid = optional_param('id',null,PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/search.php');

$navlinks = array(
        array(
                'name'=>get_string('nav_ev_mn','local_evaluations'),
                'link'=>$CFG->wwwroot.'/local/evaluations/index.php',
                'type'=>'misc'
        ),
        array(
                'name'=>get_string('nav_ev_course','local_evaluations'),
                'link'=>'',
                'type'=>'misc'
        ),



);


$nav = build_navigation($navlinks);

$PAGE->requires->css('/local/evaluations/style.css');

 $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
    
    $PAGE->set_title(get_string('nav_ev_course','local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_course','local_evaluations'));
    require_login();
    
   echo $OUTPUT->header(); 
   if(isset($courseid)){
     echo "HERE";
   }

   if(isset($searchstring)){
       $searchterms=explode(" ",$searchstring);
       $output = get_courses_search($searchterms, "fullname ASC",0, 50, &$totalcount);
       //print_r($output);
       foreach ($output as $outputs) {
           $a = html_writer::link("$PAGE->url"."?id=$outputs->id","$outputs->fullname");
           echo $a.'<br>';
       }
   }
   
   
   
   echo "
   		<form action='search.php' method='post'>
   		Search: <input type='text' name ='search'/>
   		<input type='submit' />
   		</form>

   ";
    
    
    echo '</tr></table>';
    echo $OUTPUT->footer();
    
    
    function table_header(){
        echo '<tr>';
           echo '<th>'.get_string('name_header','local_evaluations').'</th>';
   echo '<th>'.get_string('start_header','local_evaluations').'</th>';
   echo '<th>'.get_string('end_header','local_evaluations').'</th>';
   echo '<th>'.get_string('status_header','local_evaluations').'</th>';
   echo '<th>'.get_string('delete_header','local_evaluations').'</th>';
   echo '<th>'.get_string('force_s_header','local_evaluations').'</th>';
   echo '<th>'.get_string('force_c_header','local_evaluations').'</th>';
   echo '<th>'.get_string('response_count','local_evaluations').'</th>';
   echo '</tr>';
    }


?>