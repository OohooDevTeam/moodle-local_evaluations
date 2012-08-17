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

// Extend the TCPDF class to create custom Header and Footer
class anonym_report_PDF extends TCPDF {

    private $eval;
    private $course;

    //$orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false
    function __construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $eval, $course) {
        $this->eval = $eval;
        $this->course = $course;
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

        global $DB, $CFG;

// set default header data
        $this->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER + 5);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set font
        $this->SetFont('times', 'BI', 14);

        $questions = $DB->get_records('evaluation_questions', array('evalid' => $eval->id));
        $question_types = $DB->get_records('evaluations_question_types');
        
        
        $this->AddPage();     

        //General Information--------------------------------------------
        //Response Rate
        $total_students = count_students_in_course($course->id); //get users in course with role student
        $responses = get_eval_reponses_count($eval->id); //

        $percent = (($responses / $total_students) * 100);

        if ($percent > 100) {
            $percent = 100;
        }

        $output = get_string('response_rate', 'local_evaluations') . " $responses / $total_students : $percent%";


        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M');

        //Question Count
        $output = get_string('question_count', 'local_evaluations') . count($questions);
        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M');


        $this->ln();
        //-------------------------------------------------------------------

        


        if (!$questions)
            return;

        $question_i = 1;
        foreach ($questions as $question) {

            $this->AddPage();
//Question Number    
            $this->SetFont('times', 'BI', 14);
            $txt = 'blah';
            $txt .= get_string('question') . ' ' . $question_i . ":";
            $this->Cell(0, $h = 0, $txt, $border = 0, $ln = 1, $align = 'l', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M');


//Question Itself
            $this->SetFont('times', 'I', 14);
            $txt = $question->question;
            $this->MultiCell(0, 0, $txt, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);

//new line
            $this->ln();

            $this->SetFont('times', '', 12);

//responses
            $responses = $DB->get_records('evaluation_response', array('question_id' => $question->id));

            if(!is_array($responses) || empty($responses)){
                continue;
            }
            
            $class = 'question_' . $question_types[$question->type]->class;
            require_once($CFG->dirroot . '/local/evaluations/classes/question_types/' . $class . '.php');

//randomize responses order
            //shuffle($responses);

            //@TODO - I don't like how there are 2 itterations of this array - fix it
            //Create array of just responses
            $responses_data = array();
            foreach($responses as $response){
               $responses_data[] = $response->response; 
            }
            
            
            //Display the statistics
            $this->display_statistics($class,$responses_data);
            

            $response_i = 1;
            foreach ($responses as $response) {
                $output = $question_i . "." . $response_i . ". ";




                $output .= $class::process_response_for_output($response->response, $response->question_comment);

                $this->MultiCell(0, 0, $output, $border = 1, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);


                $response_i++;
            }


            $question_i++;
        }
    }

    //Page header
    public function Header() {
        // Logo
        $this->SetFont('helvetica', 'B', 20);
        // Title
        $this->Cell(0, 15, $this->eval->name, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetFont('helvetica', 'B', 12);


        $course = $this->course->fullname;
        $this->Cell(0, 15, $course, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

        $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');

    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    
     
    public function display_statistics($class,$responses_data){
        
                    //Load Statistics if applicable
            if ($class::is_averagable()) {
                $average = $class::average($responses_data);
                $this->MultiCell(0, 0, $average, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);

            }
            
                        if ($class::is_medianable()) {
                $average = $class::median($responses_data);
                $this->MultiCell(0, 0, $average, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);

            }
            
                        if ($class::is_modeable()) {
                $average = $class::mode($responses_data);
                $this->MultiCell(0, 0, $average, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);

            }
            
                        if ($class::is_rangeable()) {
                $average = $class::range($responses_data);
                $this->MultiCell(0, 0, $average, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);

            }
            
                        
           if ($class::is_count_responses()) {
                
                $average = $class::count_responses($responses_data); 
               $this->MultiCell(0, 0, $average, $border = 0, $align = 'l', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = true, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false);  
             }
    }

}


?>
