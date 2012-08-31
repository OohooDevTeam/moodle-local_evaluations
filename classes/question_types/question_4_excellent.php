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
 * This represents a evaluation question which has 4 options 
 * (Worst) [1 - 4] (Best)
 */
global $CFG;
require_once($CFG->dirroot . '/local/evaluations/classes/question.php');

class question_4_excellent extends question {

    public $type_name = "4-1 Excellent - poor"; //loaded to database on install / update

    const numeric = true;
    const max_rating = 4; //The highest possible rating for this question.

    /**
     * I can't find anywhere where this is used =/
     * 
     * @param type $mform
     * @param type $form
     * @param type $data
     * @param type $order
     */

    function display(&$mform, $form, $data, $order) {
        //Add a new header for this question.
        $mform->addElement('header', "question_header_x[$order]",
                get_string('question', 'local_evaluations') . " $order");

        $mform->addElement('static', "question[$order]", '',
                '<b>' . $this->question . '</b>');


        $abr = array(
            get_string('scale4_4', 'local_evaluations'),
            get_string('scale4_3', 'local_evaluations'),
            get_string('scale4_2', 'local_evaluations'),
            get_string('scale4_1', 'local_evaluations'),
        );

        $mform->addElement('hidden', "questionid[$order]", $this->id);

        $radioarray = array();

        for ($i = 0; $i < self::max_rating; $i++) {
            $radioarray[] = &$mform->createElement('radio', "response[$order]",
                            '', $abr[$i], self::max_rating - $i);
        }
        $mform->setDefault("response[$order]", -1);

        $mform->addGroup($radioarray, "response_grp[$order]", '',
                array('&nbsp;&nbsp;&nbsp;'), false);
        $mform->addRule("response_grp[$order]", get_string('required'),
                'required', null, 'client');

        //$mform->addHelpButton("response_grp[$order]", 'question_5_rate', 'local_evaluations');
    }

    static function process_response_for_output($response, $comment) {

        //verbous equivilent
        $verbous = self::string_equiv($response);
        $response = $response . get_string('question_4_rate_response',
                        'local_evaluations') . " : " . $verbous;

        $output = $response;
        return $output;
    }

    /**
     * Determine whether or not question has a numeric response. You can only 
     * calculate statistics on numeric question types. Non numeric types are treated
     * as comments.
     * 
     * @return boolean
     */
    static function is_numeric() {
        return self::numeric;
    }

    /**
     * Calculate the average of a series of responses from numeric questions.
     * 
     * @param int[] $responses numeric response values.
     * @return int  The average value.
     */
    static function average($responses) {
        $sum = 0;
        foreach ($responses as $response) {
            $sum += $response;
        }
        return $sum / count($responses);
    }

    /**
     * Determine if the given response is a positive response to a numeric question.
     * 
     * Positive responses count toward the %4/5 fields in the report.
     * 
     * @param int $val
     * @return boolean
     */
    static function isPositive($val) {
        return $val >= 3;
    }

    /**
     * Determine if the given response is a ngative response to a numeric question.
     * 
     * Negative responses count toward the %2/5 fields in the report.
     * 
     * @param int $val
     * @return boolean
     */
    static function isNegative($val) {
        return $val <= 2;
    }

    /**
     * Calculate the median value of a series of responses to a numeric question
     *  
     * @param int[] $responses numeric response values.
     * @return string The string "median [value] : [status]" where status is the string
     *  equivilent of the shown value.
     */
    static function median($responses) {

        $median = round(mmmr($responses, 'median'), 4);
        $verbous_average = self::string_equiv(round($median));
        $output = get_string('median', 'local_evaluations') . ' ' . $median . ' : ' . $verbous_average;
        return $output;
    }

    /**
     * Calculate the mode value of a series of responses to a numeric question
     *  
     * @param int[] $responses numeric response values.
     * @return string The string "mode [value] : [status]" where status is the string
     *  equivilent of the shown value.
     */
    static function mode($responses) {

        $mode = round(mmmr($responses, 'mode'), 4);
        $verbous_average = self::string_equiv(round($mode));
        $output = get_string('mode', 'local_evaluations') . ' ' . $mode . ' : ' . $verbous_average;

        return $output;
    }

    /**
     * Calculate the range of a series of responses to a numeric question
     *  
     * @param int[] $responses numeric response values.
     * @return string The string "range [value]"
     */
    static function range($responses) {

        $range = mmmr($responses, 'range');
        $output = get_string('range', 'local_evaluations') . ' ' . $range;

        return $output;
    }

    /**
     * Determine the string equivilent of a given response value to a numeric question.
     * 
     * @param type $response
     * @return type
     */
    static function string_equiv($response) {

        $response_string = '';

        switch ($response) {
            case 1:
                $response_string .= get_string('poor', 'local_evaluations');
                break;
            case 2:
                $response_string .= get_string('unsatisfactory',
                        'local_evaluations');
                break;
            case 3:
                $response_string .= get_string('good', 'local_evaluations');
                break;
            case 4:
                $response_string .= get_string('excellent', 'local_evaluations');
                break;
        }


        return $response_string;
    }

    /**
     * Not Used. But it seems to draw a graph of some sort so am leaving it in a 
     * reference.
     *  
     * @global type $CFG
     * @param type $responses_data
     * @return string
     */
    static function count_responses($responses_data) {
        global $CFG;
        $count_selected_response = array(); //count how many times each option was selected
        foreach ($responses_data as $response_data) {
            if (!isset($count_selected_response[$response_data])) {
                $count_selected_response[$response_data] = 1;
            } else {
                $count_selected_response[$response_data] += 1;
            }
        }

        $output = get_string('selected_count', 'local_evaluations') . '<ul>';

        $yAxis = array();
        $xAxis = array();

        for ($i = 1; $i <= self::max_rating; $i++) {
            $yAxis[] = self::string_equiv($i);

            if (!isset($count_selected_response[$i])) {
                $xAxis[] = 0;
            } else {
                $xAxis[] = $count_selected_response[$i];
            }
        }

        // Standard inclusions   
        require_once("$CFG->dirroot/local/evaluations/graphs/class/pData.class.php");
        require_once("$CFG->dirroot/local/evaluations/graphs/class/pDraw.class.php");
        require_once("$CFG->dirroot/local/evaluations/graphs/class/pImage.class.php");

        $path = sys_get_temp_dir();
        $path .= '/';
        /* Create and populate the pData object */
        $MyData = new pData();
        $MyData->addPoints($xAxis, "Choices");
        $MyData->setAxisName(0, get_string('times_chosen', 'local_evaluations'));
        $MyData->addPoints($yAxis, "Options");
        $MyData->setAxisName(1, get_string('choices', 'local_evaluations'));
        $MyData->setAbscissa("Options");

        /* Create the pChart object */
        $myPicture = new pImage(500, 200, $MyData);


        /* Define the default font */
        $myPicture->setFontProperties(array("FontName" => "$CFG->dirroot/local/evaluations/graphs/fonts/GeosansLight.ttf", "FontSize" => 8));

        /* Set the graph area */
        $myPicture->setGraphArea(100, 30, 480, 180);
        $myPicture->drawGradientArea(100, 30, 480, 180, DIRECTION_HORIZONTAL,
                array("StartR" => 200, "StartG" => 200, "StartB" => 200, "EndR" => 240, "EndG" => 240, "EndB" => 240, "Alpha" => 30));

        /* Draw the chart scale */
        $scaleSettings = array("DrawXLines" => FALSE, "Mode" => SCALE_MODE_START0, "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10, "Pos" => SCALE_POS_TOPBOTTOM);
        $myPicture->drawScale($scaleSettings);

        /* Turn on shadow computing */
        $myPicture->setShadow(TRUE,
                array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));

        /* Draw the chart */
        $myPicture->drawBarChart(array("Rounded" => TRUE, "Surrounding" => 30, "DisplayValues" => TRUE));

        $path = $path . uniqid('eval') . '.png';

        /* Render the picture (choose the best way) */
        $myPicture->render($path);



        return $path;
        //return $output;
    }

}

?>
