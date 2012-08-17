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

$handlers = array (
    'eval_complete' => array (
         'handlerfile'      => '/local/evaluations/lib.php',
         'handlerfunction'  => 'eval_complete_handler',
         'schedule'         => 'instant'
     ),
        'eval_created' => array (
         'handlerfile'      => '/local/evaluations/lib.php',
         'handlerfunction'  => 'eval_created_handler',
         'schedule'         => 'instant'
     )
);
?>
