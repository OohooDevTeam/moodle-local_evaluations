<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
global $CFG;

require_once('locallib.php');

$dept = optional_param('dept', false, PARAM_TEXT);

//security check
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);


$PAGE->set_url($CFG->wwwroot . '/local/evaluations/admin.php');

$navlinks = array(
    array(
        'name' => get_string('nav_ev_mn', 'local_evaluations'),
        'link' => $CFG->wwwroot . '/local/evaluations/index.php',
        'type' => 'misc'
    ),
    array(
        'name' => get_string('dept_selection', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    ),
);

if($dept) {
    $navlinks[1]['link'] = $CFG->wwwroot . '/local/evaluations/admin.php';
    
    $navlinks[] = array(
        'name' => get_string('nav_admin', 'local_evaluations'),
        'link' => '',
        'type' => 'misc'
    );
}

$nav = build_navigation($navlinks);


$PAGE->set_title(get_string('nav_admin', 'local_evaluations'));
$PAGE->set_heading(get_string('nav_admin', 'local_evaluations'));
$PAGE->requires->css('/local/evaluations/style.css');
require_login();

$department_list = get_departments();
$your_administrations = $DB->get_records('department_administrators', array('userid' => $USER->id));

$your_depts = array();
foreach ($your_administrations as $administration) {
    $your_depts[$administration->department] = $department_list[$administration->department];
}

if (count($your_depts) == 0) {
    print_error('You are not an adminstrator for any departments');
}
echo $OUTPUT->header();

if ($dept !== false && is_dept_admin($dept, $USER)) {
//Display Form

    echo '<ol>';
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/change_preamble.php?dept=' . $dept . '">' . get_string('preamble', 'local_evaluations') . '</a></li>';
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/standard_questions.php?dept=' . $dept . '">' . get_string('nav_st_qe', 'local_evaluations') . '</a></li>';
    echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/coursecompare.php?dept=' . $dept . '">' . get_string('nav_cs_mx', 'local_evaluations') . '</a></li>';

    echo '</ol>';
} else {
    echo '<ol>';
    foreach ($your_depts as $dept_code => $deptartment) {
        echo '<li><a href="' . $CFG->wwwroot . '/local/evaluations/admin.php?dept=' . $dept_code . '"> ' . $deptartment . '</a></li>';
    }
    echo'</ol>';
}
echo $OUTPUT->footer();
?>
