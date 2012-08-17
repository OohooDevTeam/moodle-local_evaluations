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



if ($ADMIN->fulltree) {
$settings = new admin_settingpage('localsettings'.'evaluations', get_string('evaluation','local_evaluations'), 'moodle/site:config', false);
$ADMIN->add('localplugins', $settings);
    
    //Preamble
    $settings->add(new admin_setting_confightmleditor('local_eval_preamble', get_string('eval_preamble_header', 'local_evaluations'),
                       get_string('eval_preamble_desc', 'local_evaluations'), ''));            

    $settings->add(new admin_setting_configtext('local_eval_early_message_delay', get_string('early_message_delay', 'local_evaluations'),
                       get_string('early_message_delay_desc', 'local_evaluations'), 15, PARAM_INT));
   
    $settings->add(new admin_setting_configtext('local_eval_message_que_limit', get_string('message_que_limit', 'local_evaluations'),
                       get_string('message_que_limit_desc', 'local_evaluations'), 2, PARAM_INT));

    
}

?>
