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
require_once('forms/administrator_form.php');

require_login();
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

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url($CFG->wwwroot . '/local/evaluations/administrator.php');
$PAGE->set_heading(get_string('administration', 'local_evaluations'));
$PAGE->set_title(get_string('administration', 'local_evaluations'));
$PAGE->requires->js(new moodle_url('js/jquery-1.7.1.js'), true);
$PAGE->requires->js(new moodle_url('js/jquery_searchable.js'), true);


$dept = optional_param('dept', false, PARAM_TEXT);

echo $OUTPUT->header();



if (has_capability('local/evaluations:admin', $context)) {
    if ($dept) {
        if (array_key_exists('add', $_REQUEST) && array_key_exists('add_user', $_REQUEST)) {
            foreach ($_REQUEST['add_user'] as $userid) {
                $records = $DB->get_records_select('department_administrators', 'userid = ' . $userid . ' AND department = \'' . $dept . '\'');
                if (empty($records)) {
                    $user = new stdClass();
                    $user->userid = $userid;
                    $user->department = $dept;
                    $DB->insert_record('department_administrators', $user);
                }
            }
        } else if (array_key_exists('remove', $_REQUEST) && array_key_exists('remove_user', $_REQUEST)) {
            foreach ($_REQUEST['remove_user'] as $userid) {
                $DB->delete_records_select('department_administrators', 'userid = ' . $userid . ' AND department = \'' . $dept . '\'');
            }
        }

        $mform = new admin_form($dept);
        $mform->display();
    } else {
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
