<?php
/**
 * ************************************************************************
 * *                             Evaluations                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluations                                              **
 * @name        Evaluations                                              **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */
/**
 * This is the main entry point for the evaluations plugin. It is a menu screen
 * that you will see when you first access the plugin.
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');

//Create the breadcrumbs
$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    ),
);
$nav = build_navigation($navlinks);

//Set page headers.
$PAGE->set_title(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();

//Check if the user is the administrator of any departments.
$department_list = get_departments();
$your_administrations = $DB->get_records('department_administrators', array('userid' => $USER->id));

$your_depts = array();
foreach ($your_administrations as $administration) {
    $your_depts[$administration->department] = $department_list[$administration->department];
}
//If the user is a department administrator or is a global admin then they 
//should have admin access to this page.
$admin_access = count($your_depts) != 0 || has_capability('local/evaluations:admin', $context);

//Display Menu
echo $OUTPUT->header();

echo '<ol>';
//The order of these list items was carefully determined.The if statements have been seperated
//so that if you decide to it would be easy to re-orde these things.

//If the user is a global admin then they should be given an option to go to the
//administration page. Where they can assign dept admins.
if (has_capability('local/evaluations:admin', $context)) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/administration.php">' . get_string('administration', 'local_evaluations') . '</a></li>';
}

//If the user has admin access then give them access to department administration.
if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/admin.php">' . get_string('nav_admin', 'local_evaluations') . '</a></li>';
}

//If the user has admin access then give them access to department evaluation administation.
if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/evaluations.php">' . get_string('nav_ev_mn', 'local_evaluations') . '</a></li>';
}

//If the user has no admin access then just let them see the open evaluations page.
echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/evals.php">' . get_string('evaluations', 'local_evaluations') . '</a></li>';

//If the user has admin access the give them access to department evaluation reports.
if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/reports.php">' . get_string('nav_reports', 'local_evaluations') . '</a></li>';
}

echo '</ol>';

echo $OUTPUT->footer();
?>