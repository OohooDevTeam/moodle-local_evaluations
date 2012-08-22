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
 * Displays the preamble form.
 */
require_once("$CFG->libdir/formslib.php");

class preamble_form extends moodleform {

    private $dept;
    function __construct($dept){
        $this->dept = $dept;
        parent::__construct();
    }
    protected function definition() {
        global $DB;
        $mform = $this->_form;
        
        $mform->addElement('header', 'preamble_header', get_string('preamble', 'local_evaluations'));
        $mform->addElement('textarea', 'preamble', get_string('preamble', 'local_evaluations'), 'rows="10" cols="50"');
        $mform->addElement('hidden', 'dept', $this->dept);
        
        //Lets see if it already exists.
        
        if($record = $DB->get_record_select('department_preambles', "department = '$this->dept'")) {
            $mform->setDefault('preamble', $record->preamble);
        }
        
        $this->add_action_buttons(false);
    }

}


?>
