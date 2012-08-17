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


//security check
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);



$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    ),
);

$nav = build_navigation($navlinks);


$PAGE->set_title(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_ev_mn', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();


$department_list = get_departments();
$your_administrations = $DB->get_records('department_administrators', array('userid' => $USER->id));

$your_depts = array();
foreach ($your_administrations as $administration) {
    $your_depts[$administration->department] = $department_list[$administration->department];
}

$admin_access = count($your_depts) != 0 || has_capability('local/evaluations:admin', $context);
$courses = get_instructing_courses();

//Display Form
echo $OUTPUT->header();

echo '<ol>';
//They wanted these in a specific order.
if (has_capability('local/evaluations:admin', $context)) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/administration.php">' . get_string('administration', 'local_evaluations') . '</a></li>';
}
if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/admin.php">' . get_string('nav_admin', 'local_evaluations') . '</a></li>';
}
if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/evaluations.php">' . get_string('nav_ev_mn', 'local_evaluations') . '</a></li>';
}
echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/evals.php">' . get_string('evaluations', 'local_evaluations') . '</a></li>';

if ($admin_access) {
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/reports.php">' . get_string('nav_reports', 'local_evaluations') . '</a></li>';
}
echo '</ol>';

echo $OUTPUT->footer();
?>