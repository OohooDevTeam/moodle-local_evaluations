<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/evaluations/locallib.php');

class admin_form extends moodleform {

    private $dept;

    function __construct($dept) {
        $this->dept = $dept;
        parent::__construct();
    }

    function definition() {
        global $DB, $OUTPUT;

        $mform = & $this->_form;
        $depts = get_departments();

        if (!array_key_exists($this->dept, $depts)) {
            return;
        }

        if (isset($this->dept)) {
            $mform->addElement('header', '', $depts[$this->dept]);
            $mform->addElement('html', '<div style = "width: 100%; height: 100%; overflow: hidden;">');
            $mform->addElement('hidden', 'dept', $this->dept);
            $mform->addElement('html', '<table style="width: 100%">');
            $mform->addElement('html', '<tr style="vertical-align: top">');

            $invigilators = $DB->get_records_select('department_administrators', 'department =' . "'$this->dept'");

            $users = array();

            if (!empty($invigilators)) {
                foreach ($invigilators as $aUser) {
                    $user = $DB->get_record('user', array('id' => $aUser->userid));
                    $user->fullname = $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')';
                    $users[] = $user;
                }
            }

            $mform->addElement('html', '<td style="width: 300px; padding: 5px">');
            $mform->addElement('html', '<select name="remove_user[]" multiple="multiple" id="id_remove_user" style="width: 300px; height: 300px; " >');
            foreach ($users as $user) {
                $mform->addElement('html', '<option value="' . $user->id . '">' . $user->fullname . '</option>');
            }
            $mform->addElement('html', '</select>');

            $mform->addElement('html', '</td>');


            $mform->addElement('html', '<td style="vertical-align:middle; text-align:center; width: 100px; padding: 5px;">' . '<input style="width: 100px" name="add" id="add" type="submit" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '"/>' . '</br>');
            $mform->addElement('html', '<input style="width: 100px" name="remove" id="remove" type="submit" value="' . $OUTPUT->rarrow() . '&nbsp;' . get_string('remove') . '" title="' . get_string('remove') . '"/>' . '</td>');

            $users = array();
            $user_list = $DB->get_records('user');

            foreach ($user_list as $aUser) {
                $record = $DB->get_record_select('department_administrators', 'department =' . "'$this->dept' AND userid = $aUser->id");
                if (!$record && $aUser->id != 1) {
                    $aUser->fullname = $aUser->firstname . ' ' . $aUser->lastname . ' (' . $aUser->email . ')';
                    $users[] = $aUser;
                }
            }

            $mform->addElement('html', '<td>');
            $mform->addElement('html', '<select name="add_user[]" multiple="multiple" id="id_add_user" style="width: 300px; height: 300px; " >');
            foreach ($users as $user) {
                $mform->addElement('html', '<option value="' . $user->id . '">' . $user->fullname . '</option>');
            }
            $mform->addElement('html', '</select>');
            $mform->addElement('html', '</td>');


            $mform->addElement('html', '</tr>');
            $mform->addElement('html', '</table>');

            $script = '<script>';
            $script .= '$("#id_add_user").searchable({background_color: "#f0f0f0"});';
            $script .= '$("#id_remove_user").searchable({background_color: "#f0f0f0"});';

            //Make searchbar visible. (It doesn't work properly in a multiselect list.)
            $script .= "$('#id_add_user').find('div').css({'position' : 'static', 'border' : 'solid 1px', 'border-bottom' : 'solid 0px', 'width' : $('#id_add_user').width()}).prependTo($('#id_add_user').parent());";
            $script .= "$('#id_add_user').css('padding', '0px');";
            //Make searchbar visible. (It doesn't work properly in a multiselect list.)
            $script .= "$('#id_remove_user').find('div').css({'position' : 'static', 'border' : 'solid 1px', 'border-bottom' : 'solid 0px', 'width' : $('#id_add_user').width()}).prependTo($('#id_remove_user').parent());";
            $script .= "$('#id_remove_user').css('padding', '0px');";

            $script .= '</script>';

            $mform->addElement('html', $script);
            $mform->addElement('html', '</div>');
        }
    }

}
?>

