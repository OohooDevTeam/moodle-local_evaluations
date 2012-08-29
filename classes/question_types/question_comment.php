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
global $CFG;
require_once($CFG->dirroot . '/local/evaluations/classes/question.php');

class question_comment extends question {

    public $type_name = "Comment"; //loaded to database on install / update

    const numeric = false;
    const max_rating = 1; //Means nothing.

    function display(&$mform, $form, $data, $order) {
        $mform->addElement('header', "question_header_x[$order]",
                get_string('question', 'local_evaluations') . " $order");

        $mform->addElement('static', "question[$order]", '',
                '<b>' . $this->question . '</b>');
        $mform->addElement('hidden', "response[$order]", '');
        $mform->addElement('hidden', "questionid[$order]", $this->id);

        $mform->addElement('textarea', "comments[$order]",
                get_string('comments', 'local_evaluations'),
                array('rows' => 8, 'cols' => 65));
        //$mform->addElement('htmleditor', "comments[$order]", get_string('comments', 'local_evaluations'));
        $mform->setType('text', PARAM_RAW);
    }

    static function process_response_for_output($response, $comment) {

        $output .= get_string('comments_c', 'local_evaluations');
        $output .= $comment;

        return $output;
    }

    static function is_numeric() {
        return self::numeric;
    }

}

?>
