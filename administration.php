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
 * This page allows the global admin to assign users as department administrators.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms/administrator_form.php');

// ----- Parameters ----- //
$dept = optional_param('dept', false, PARAM_TEXT);

// ----- Security ----- //
require_login();

// ----- Navigation ----- //
//build breadcrumbs
$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('area_admins', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/administration.php',
        'type' => 'misc'
    )
);
build_navigation($navlinks);

// ----- Stuff ----- //
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/administrator.php');
$PAGE->set_heading(get_string('administration', 'local_evaluations'));
$PAGE->set_title(get_string('administration', 'local_evaluations'));
$PAGE->requires->js(new moodle_url('js/jquery-1.7.1.js'), true);
$PAGE->requires->js(new moodle_url('js/jquery_searchable.js'), true);

// ----- Output ----- //
echo $OUTPUT->header();

//Make sure the user is a global admin.
if (has_capability('local/evaluations:admin', $context)) {
    //If a department is specified then display the assignment form.
    if ($dept) {
        //Check if the addbutton was pressed and users were selected.
        if (array_key_exists('add', $_REQUEST)
                && array_key_exists('add_user', $_REQUEST)) {

            //If it was then we need to add each user as an admin if they aren't already.
            foreach ($_REQUEST['add_user'] as $userid) {
                
                //Check if the user is already an admin for this department.
                $records = $DB->get_records_select('department_administrators',
                        'userid = ' . $userid . ' AND department = \'' . $dept . '\'');
                
                //If no records were returned then the user is not an admin for 
                //this department.
                if (empty($records)) {
                    $user = new stdClass();
                    $user->userid = $userid;
                    $user->department = $dept;
                    $DB->insert_record('department_administrators', $user);
                }
                
            }
        //Check if the remove button was pressed and users were selected.
        } else if (array_key_exists('remove', $_REQUEST)
                && array_key_exists('remove_user', $_REQUEST)) {
            
            //For each user in the list of selected users remove them as admins
            //for this department.
            foreach ($_REQUEST['remove_user'] as $userid) {
                //Delete the record in the database.  If it doesnt exist this 
                //function does nothing therefore we don't need to do a check.
                $DB->delete_records_select('department_administrators',
                        'userid = ' . $userid . ' AND department = \'' . $dept . '\'');
            }
        }

        //Show the added user lists.
        $mform = new admin_form($dept);
        $mform->display();
    } else {
        //If a department was not specified then create a list of departments to
        //choose from.
        echo '<ol>';
        $depts = get_departments();
        foreach ($depts as $code => $dept) {
            echo '<li><a href="administration.php?dept=' . $code . '">' . $dept . '</a></li>';
        }
        echo '</ol>';
    }
}

echo $OUTPUT->footer();
?>
