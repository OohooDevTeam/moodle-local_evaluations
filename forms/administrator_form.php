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
 * This is the form that a global admin will use to assign department administrators.
 */
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/evaluations/locallib.php');

class admin_form extends moodleform {

    //The selected department.
    private $dept;

    function __construct($dept) {
        $this->dept = $dept;
        parent::__construct();
    }

    function definition() {
        global $DB, $OUTPUT;

        $mform = & $this->_form;

        //Make sure that the given department exists.
        $depts = get_departments();

        if (!isset($this->dept) || !array_key_exists($this->dept, $depts)) {
            return;
        }

        //Now that we know the department exists we can create the form.
        $mform->addElement('header', '', $depts[$this->dept]);

        $mform->addElement('html',
                '<div style = "width: 100%; height: 100%; overflow: hidden;">');
        $mform->addElement('hidden', 'dept', $this->dept);

        //We're going to format into 3 columns using a table. First column is
        //the current admins, second we will put the add/remove buttons
        //and in the last we will put all the users in the system.
        $mform->addElement('html', '<table style="width: 100%">');
        $mform->addElement('html', '<tr style="vertical-align: top">');

        //Get list of all admins for this department already.
        $invigilators = $DB->get_records_select('department_administrators',
                'department =' . "'$this->dept'");

        //Convert all these ingivilators into user objects
        $users = array();

        if (!empty($invigilators)) {
            foreach ($invigilators as $aUser) {
                $user = $DB->get_record('user', array('id' => $aUser->userid));

                //Add a full name field which will be displayed later.
                $user->fullname = $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')';
                $users[] = $user;
            }
        }

        //Add the first column
        $mform->addElement('html', '<td style="width: 300px; padding: 5px">');

        //Input the select box. -- It is named remove_user[] because anyone selected
        //when the form is submited with the remove button will be removed from the list.
        $mform->addElement('html',
                '<select name="remove_user[]" multiple="multiple" id="id_remove_user" style="width: 300px; height: 300px; " >');

        //Assign userids as values and show the name we created earlier.
        foreach ($users as $user) {
            $mform->addElement('html',
                    '<option value="' . $user->id . '">' . $user->fullname . '</option>');
        }
        $mform->addElement('html', '</select>');

        $mform->addElement('html', '</td>');

        //Now build the second column.
        $mform->addElement('html',
                '<td style="vertical-align:middle; text-align:center; width: 100px; padding: 5px;">');
        
        //Add the "add" and "remove" buttons.
        $mform->addElement('html',
                '<input style="width: 100px" name="add" id="add" type="submit" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '"/>' . '</br>');
        $mform->addElement('html',
                '<input style="width: 100px" name="remove" id="remove" type="submit" value="' . $OUTPUT->rarrow() . '&nbsp;' . get_string('remove') . '" title="' . get_string('remove') . '"/>');
        $mform->addElement('html', '</td>');
        
        $users = array();
        $user_list = $DB->get_records('user');
        
        //Add in the non department admin list.
        foreach ($user_list as $aUser) {
            $record = $DB->get_record_select('department_administrators',
                    'department =' . "'$this->dept' AND userid = $aUser->id");
            if (!$record && $aUser->id != 1) {
                $aUser->fullname = $aUser->firstname . ' ' . $aUser->lastname . ' (' . $aUser->email . ')';
                $users[] = $aUser;
            }
        }
        
        
        $mform->addElement('html', '<td>');
        
        //Input the select box. -- It is named add_user[] because anyone selected
        //when the form is submited with the add button will be removed from the list.
        $mform->addElement('html',
                '<select name="add_user[]" multiple="multiple" id="id_add_user" style="width: 300px; height: 300px; " >');
        foreach ($users as $user) {
            $mform->addElement('html',
                    '<option value="' . $user->id . '">' . $user->fullname . '</option>');
        }
        $mform->addElement('html', '</select>');
        $mform->addElement('html', '</td>');


        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '</table>');

        $script = '<script>';
        //Make each multiselect searchable.
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
?>

