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
require_once($CFG->dirroot . '/local/evaluations/lib.php');

class admin_form extends moodleform {

  //The selected department.
  private $dept;
  
  // Admins for the selected department
  private $current_admins, $potential_admins;
  
  function __construct($dept) {
    $this->dept = $dept;
    $this->current_admins = new evaluation_admins_existing_selector($dept);
    $this->current_admins->set_extra_fields(array('username', 'email'));
    $this->potential_admins = new evaluation_admins_potential_selector();
    $this->potential_admins->set_extra_fields(array('username', 'email'));
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
    $mform->addElement('html', '<div id="addadmisform">');
    $mform->addElement('html', '<h3 class="main">' . $depts[$this->dept] . '</h3>');
    $mform->addElement('html', '<div>');
    
    $mform->addElement('hidden', 'dept', $this->dept);
    
    //We're going to format into 3 columns using a table. First column is
    //the current admins, second we will put the add/remove buttons
    //and in the last we will put all the users in the system.
    $mform->addElement('html', '<table class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">');
    $mform->addElement('html', '<tr>');
    
    $mform->addElement('html', '<td id="existingcell">');
    $mform->addElement('html', '<p><label for="remove_user">Current administrators</label></p>');
    $mform->addElement('html', $this->current_admins->display(true));
    $mform->addElement('html', '</td>');
    
    $mform->addElement('html', '<td id="buttonscell">');
    $mform->addElement('html', '<p class="arrow_button">');
    $mform->addElement('html', '<input name="add" id="add" type="submit" value="'
		       . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="'
		       . get_string('add') . '"/>' . '</br>');
    $mform->addElement('html', '<input name="remove" id="remove" type="submit" value="'
		       . $OUTPUT->rarrow() . '&nbsp;' . get_string('remove') . '" title="'
		       . get_string('remove') . '"/>');
    $mform->addElement('html', '</p>');
    $mform->addElement('html', '</td>');
    
    $mform->addElement('html', '<td id="potentialcell">');
    $mform->addElement('html', '<p><label for="add_user">Users</label></p>');
    $mform->addElement('html', $this->potential_admins->display(true));
    $mform->addElement('html', '</td>');
    
    $mform->addElement('html', '</tr>');
    $mform->addElement('html', '</table>');
    $mform->addElement('html', '</div>');
    $mform->addElement('html', '</div>');
    
  }
  
}
?>

