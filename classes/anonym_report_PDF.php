<?php

// Extend the TCPDF class to create custom Header and Footer
class anonym_report_PDF extends TCPDF {

    private $eval;
    private $course;

    //$orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false
    function __construct($orientation, $unit, $format, $unicode, $encoding,
            $diskcache, $eval, $course, $teacher, $dept) {
        $this->eval = $eval;
        $this->course = $course;
        $this->teacher = $teacher;
        parent::__construct($orientation, $unit, $format, $unicode, $encoding,
                $diskcache);

        global $DB, $CFG;
// set default header data
        $this->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH,
                PDF_HEADER_TITLE, PDF_HEADER_STRING);

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
        $this->SetFont('times', 'B', 12);

        $questions = $DB->get_records('evaluation_questions',
                array('evalid' => $eval->id));
        $question_types = $DB->get_records('evaluations_question_types');


        $this->AddPage();

        //General Information--------------------------------------------
        //Response Rate
        $total_students = count_students_in_course($course->id); //get users in course with role student
        $responses = get_eval_reponses_count($eval->id); //
        $totalStudentResponses = $responses;
        $totStdQ = count_std_questions($dept);

        //calculates the percent submitted
        $percent = round((($responses / $total_students) * 100));

        if ($percent > 100) {
            $percent = 100;
        }

        $output = get_string('response_rate', 'local_evaluations') . " $responses / $total_students : $percent%";


        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l',
                $fill = false, $link = '', $stretch = 0,
                $ignore_min_height = false, $calign = 'T', $valign = 'M');

        //Question Count
        $output = get_string('question_count', 'local_evaluations') . count($questions);
        $this->Cell(0, $h = 0, $output, $border = 0, $ln = 1, $align = 'l',
                $fill = false, $link = '', $stretch = 0,
                $ignore_min_height = false, $calign = 'T', $valign = 'M');

        //output the questions in this report
        $this->ln();

        if ($questions) {
            $question_i = 1;
            foreach ($questions as $question) {
                $this->SetFont('times', '', 12);
                $txt = "Question $question_i : ";
                $txt .= strip_tags($question->question);
                $this->MultiCell(0, 0, $txt, $border = 0, $align = 'l',
                        $fill = false, $ln = 1, $x = '', $y = '',
                        $reseth = true, $stretch = 0, $ishtml = true,
                        $autopadding = true, $maxh = 0, $valign = 'T',
                        $fitcell = false);
                $question_i++;
            }
        }

        $this->ln();

        /**
         * This section will be printing out the table for each individual student/their responses.
         */
        //here we will print out each students responses to the questions above
        $this->SetFont('DejaVuSans', '', 9);
        $this->AddPage();

        //$tbl_header = '<table style ="width: 100%; border-style:solid; border-width:1px;" cellspacing="0">';



        $tbl_header = '<table class="top" algin="center">';
        $tbl_footer = '</table>';
        $tbl = '';

        //setting up the faux css to style our table so it looks nice
        $tbl .= '
        <style type="text/css">

        table.top td {
            border-width: 1px;
            padding: 1px;
            border-style: inset;
            border-color: black;
            background-color: white;
            -moz-border-radius: ;
        }
        </style>
        ';
        $topcspan = $totStdQ + 1;

        $tbl .= "<tr>";
        $tbl .= "<th>Stu.</th>";
        for ($i = 1; $i <= $totStdQ; $i++) {
            $q = "Q$i";
            $tbl .= "<th align = \"center\" > $q </th>";
        }

        $tbl .= "<th align = \"center\" style=\"border-left:2px solid black;\">x&#772;</th>";
        $tbl .= "<th align = \"center\" > %4/5 </th>";
        $tbl .= "<th align = \"center\" > %1/2 </th>";
        $tbl .= "</tr>";

        $u_num = 1;
        $users = get_eval_uids($eval->id);
        //printing out each number
        foreach ($users as $user) {

            $responds = get_responses_for_user($eval->id, $user->user_id);
            $tbl .= '<tr>';
            $tbl .= "<td align=\"center\" style=\"border-right:1px solid black;\"> $u_num </td>";
            $total = 0; //used to calculate the mean for this "response"
            $numPos = 0;
            $numNeg = 0;

            //If responsds is less than stdQ then fill it with zeroes. (So old report with less std questions will display properly
            $difference = ($totStdQ - count($responds));
            for ($i = 0; $i < $difference; $i++) {
                $respond = new stdClass();
                $respond->response = 0;
                $responds[] = $respond;
            }

            $i = 0;
            foreach ($responds as $respond) {

                //Dont outut extra.
                if ($i >= $totStdQ) {
                    break;
                }

                $tbl .= "<td align=\"center\"> $respond->response</td>";
                $total += $respond->response;
                $aQuestion = $DB->get_record('evaluation_questions',
                        array('id' => $respond->question_id));
                $type = $DB->get_record('evaluations_question_types',
                        array('id' => $aQuestion->type));

                require_once('question_types/question_' . $type->class . '.php');
                eval('$max = question_' . $type->class . '::max_rating;');

                if ($respond->response / $max > 0.5) {

                    $numPos++;
                } else {
                    $numNeg++;
                }

                $i++;
            }
            //now calculate the mean/median/mode 
            $mean = round($total / $totStdQ, 2);

            //calculate the percent positive/negative votes for each row
            $perPos = round(($numPos / $totStdQ) * 100);
            $perNeg = round(($numNeg / $totStdQ) * 100);

            $tbl .="<td align = \"center\" style=\"border-left:2px solid black;\">$mean</td>";
            $tbl .="<td align = \"center\" > $perPos</td>";
            $tbl .="<td align = \"center\" > $perNeg </td>";
            $tbl .="</tr>";
            $u_num++;
            //unset($stdArray);
        }

        $totalQuestions = array();

        foreach ($questions as $question) {
            $type = $DB->get_record('evaluations_question_types',
                    array('id' => $question->type));
            if ($type->class == 'comment' || $type->class == 'years') {
                continue;
            }
            //responses get stored in an array where the keys denote the response answer and the
            // data represents the number of people who responded that way.

            $qDetail = array();
            $qDetail[1] = 0;
            $qDetail[2] = 0;
            $qDetail[3] = 0;
            $qDetail[4] = 0;
            $qDetail[5] = 0;

            $responses = $DB->get_records('evaluation_response',
                    array('question_id' => $question->id));

            foreach ($responses as $resp) {

                $qDetail[$resp->response]++;
            }

            array_push($totalQuestions, $qDetail);
        }

        //calculate the mean of each collumn
        //and add up all the results for a whole table mean
        $totalMean = 0; //totalMean adds up all the resulsts in the table, to be used later

        $tbl.="<tr><td align = \"center\" style=\"border-right: 1px solid black; border-top:2px solid black;\">x&#772;</td>";
        for ($i = 0; $i < $totStdQ; $i++) {
            //for question i
            //calculate the mean
            $totalQuestionMean = 0;
            for ($j = 1; $j < 6; $j++) {
                $totalMean += $totalQuestions[$i][$j] * $j;
                $totalQuestionMean += $totalQuestions[$i][$j] * $j;
            }
            $totalQuestionMean = round($totalQuestionMean / $totalStudentResponses,
                    2);
            $tbl.= "<td align=\"center\" style=\"border-top:2px solid black\";>$totalQuestionMean</td>";
        }

        //calculates the total mean of the table
        $totalMean = round($totalMean / ($totalStudentResponses * $totStdQ), 2);
        $tbl.= "<td align = \"center\" style = \"border:2px solid black\";> $totalMean</td>";

        $tbl.="</tr>";


        //calculate the percent positive/negative ignoring 3's
        $tbl.="<tr><td align = \"center\" style=\"border-right: 1px solid black;\">%4/5</td>";
        $percentPosValues = array();
        $percentNegValues = array();
        foreach ($questions as $question) {

            $type = $DB->get_record('evaluations_question_types',
                    array('id' => $question->type));

            if ($type->class == 'comment' || $type->class == 'years') {
                continue;
            }

            require_once('question_types/question_' . $type->class . '.php');
            eval('$max = question_' . $type->class . '::max_rating;');

            $responses = $DB->get_records('evaluation_response',
                    array('question_id' => $question->id));

            $percentPos = 0;
            $percentNeg = 0;

            foreach ($responses as $resp) {

                if ($resp->response / $max > 0.5) {
                    $percentPos++;
                } else {
                    $percentNeg++;
                }
            }

            array_push($percentPosValues, $percentPos);
            array_push($percentNegValues, $percentNeg);

            $posPer = round(($percentPos / ($totalStudentResponses)) * 100);

            $tbl.="<td align=\"center\">$posPer</td>";
        }
        $tbl.="</tr>";
        $tbl.="<tr><td align = \"center\" style=\"border-right: 1px solid black;\">%1/2</td>";

        for ($i = 0; $i < $totStdQ; $i++) {
            // $negPer = round(($percentNegValues[$i] / ($percentPosValues[$i] + $percentNegValues[$i]))*100);
            $negPer = round(($percentNegValues[$i] / ($totalStudentResponses)) * 100);
            $tbl.="<td align=\"center\">$negPer</td>";
        }

        $tbl .= "</tr>";


        //now we need to check if there are other courses to compare this to. Then calculate the averages of all those other
        //evals, and compare it to this one.
        //if there is evals in the compare_eval table
        $sqlN = "SELECT count(*) from {evaluation_compare}";
        if ($DB->count_records_sql($sqlN) == 1) {
            $facultyTotalMean = 0; //used to store averages.
            $facultyTotalQuestionMean = array();
            $evalTotal = 0; //the number of evals that we are comparing.
            //$evals= array();           
            $sqlN = "SELECT evalids FROM {evaluation_compare}";

            $compareEvals = $DB->get_records_sql($sqlN); //getting the eval id numbers
            foreach ($compareEvals as $c) {
                $evals = explode(';', $c->evalids); //now we have an array of all the evals
            }

            for ($it = 0; $it < sizeof($evals) - 1; $it++) {//this is where we iterate through all the evals
                $singleEvalArray = array();
                $singleEval = $DB->get_record('evaluations',
                        array('id' => $evals[$it]));
                $singleUsers = get_eval_uids($singleEval->id);
                $numStudents = 0;
                foreach ($singleUsers as $singleUser) {
                    $responds = get_responses_for_user($singleEval->id,
                            $singleUser->user_id);
                    $index = 0;
                    foreach ($responds as $respond) {
                        $singleEvalArray[$index] += $respond->response;
                        $index++;
                    }
                    $numStudents++;
                }
                //calculate the mean for each question/course
                $singtot = 0;
                for ($k = 0; $k < $totStdQ; $k++) {
                    //$singtot += $singleEvalArray[$k];
                    $m = ($singleEvalArray[$k] / $numStudents); //calculating individual question means
                    $facultyTotalQuestionMean[$k] += $m;
                    $singtot += $m;
                }
                $facultyTotalMean += ($singtot / $totStdQ); //calculates the mean of the questions
                $evalTotal++;
            } //finished iterating through all the evals

            $tbl.="<tr><td align = \"center\" style=\"border-top: 2px solid black; border-right: 1px solid black;\">Faculty x&#772;</td>";
            for ($j = 0; $j < $totStdQ; $j++) {
                //gets the individual means for each question
                $ini = round($facultyTotalQuestionMean[$j] / $evalTotal, 2);
                $tbl .= "<td align = \"center\" style=\"border-top:2px solid black;\">$ini</td>";
            }
            //calculates the total mean through the entire faculty
            $fm = round($facultyTotalMean / $evalTotal, 2);
            //$fm = $facultyTotalMean;
            $tbl.="<td align = \"center\" style=\"border: 2px solid black;\">$fm</td>";
            $tbl.="</tr>";
        }
        //if there are comprable evals
        //writing the percentages/stats table to the page
        $this->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false,
                false, '');


        /* This seciont is used to print out the non standard questions. */
        $this->AddPage();

        $tbl_header = '<table class="top" algin="center">';
        $tbl_footer = '</table>';
        $tbl = '';

        //setting up the faux css to style our table so it looks nice
        $tbl .= '
        <style type="text/css">

        table.top td {
            border-width: 1px;
            padding: 1px;
            border-style: inset;
            border-color: black;
            background-color: white;
            -moz-border-radius: ;
        }
        </style>
        ';

        $tbl .= "<tr>";
        $tbl .= "<th>Stu.</th>";
        $users = get_eval_uids($eval->id);

        //Get the number of extra questions. By grabbing the first user then counting the number of questions he has.
        if (count($users) > 0) {
            $first = array_shift($users);
            array_unshift($users, $first);
            $columns = count(get_responses_for_user($eval->id, $first->user_id)) - $totStdQ;
        } else {
            $columns = 0;
        }
        for ($i = 1; $i <= $columns; $i++) {

            $q = "Q" . ($totStdQ + $i);

            $tbl .= "<th align = \"center\" > $q </th>";
        }

        $tbl .= "<th align = \"center\" style=\"border-left:2px solid black;\">x&#772;</th>";
        $tbl .= "<th align = \"center\" > %4/5 </th>";
        $tbl .= "<th align = \"center\" > %1/2 </th>";
        $tbl .= "</tr>";

        $u_num = 1;

        //printing out each number
        foreach ($users as $user) {
            $responds = get_responses_for_user($eval->id, $user->user_id);
            $tbl .= '<tr>';
            $tbl .= "<td align=\"center\" style=\"border-right:1px solid black;\"> $u_num </td>";
            $total = 0; //used to calculate the mean for this "response"
            $num5 = 0;
            $num4 = 0;
            $num3 = 0;
            $num2 = 0;
            $num1 = 0;

            //If responsds is less than stdQ then fill it with zeroes. (So old report with less std questions will display properly
            $non_std_responds = array();
//            $difference = ($totStdQ - count($responds));
            $i = 0;
            foreach ($responds as $respond) {
                if ($i >= $totStdQ) {
                    $non_std_responds[] = $respond;
                }
                $i++;
            }
//
//            //We want to keep the same structure as before.
//            $difference = ($totStdQ - count($non_std_responds));
//            for ($i = 0; $i < $difference; $i++) {
//                $respond = new stdClass();
//                $respond->response = 'X';
//                $non_std_responds[] = $respond;
//            }

            $i = 0;
            foreach ($non_std_responds as $respond) {

//                //Dont outut extra.
//                if ($i >= $totStdQ) {
//                    break;
//                }

                $tbl .= "<td align=\"center\"> $respond->response</td>";
                $total += $respond->response;
                switch ($respond->response) {
                    case 1:
                        $num1++;
                        break;
                    case 2:
                        $num2++;
                        break;
                    case 3:
                        $num3++;
                        break;
                    case 4:
                        $num4++;
                        break;
                    case 5:
                        $num5++;
                        break;
                }
                $i++;
            }
            //now calculate the mean/median/mode 
            $mean = round($total / $columns, 2);

            //calculate the percent positive/negative votes for each row
            $perPos = round((($num5 + $num4) / $columns) * 100);
            $perNeg = round((($num1 + $num2) / $columns) * 100);

            $tbl .="<td align = \"center\" style=\"border-left:2px solid black;\">$mean</td>";
            $tbl .="<td align = \"center\" > $perPos</td>";
            $tbl .="<td align = \"center\" > $perNeg </td>";
            $tbl .="</tr>";
            $u_num++;
            //unset($stdArray);
        }
        $this->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false,
                false, '');

        /*         * *********************************************************************
         * 
         * This section used to print out each individuals comments on the class
         *
         * ********************************************************************* */

        $this->AddPage();
        //print out all the comments associated with this;

        $uname = 1;
        $comment_info = '';

        $comment_array = array();

        foreach ($users as $user) {
            $user_comments = array();
            foreach (get_user_comments($eval->id, $user->user_id) as $comment) {
                $user_comments[] = $comment;
            }
            $comment_array[] = $user_comments;
        }

        //Makes sure there are users\comments in the system.
        if (($num_users = count($comment_array)) != 0 && ($num_comments = count($comment_array[0])) != 0) {
            for ($i = 0; $i < $num_comments; $i++) {
                $c = $comment_array[0][$i];
                $comment_info .= '<strong>' . $c->question . "</strong>";
                $comment_info .= '<pre>';
                $comment_info .= '<hr/><ol>';
                for ($j = 0; $j < $num_users; $j++) {

                    $comment_info .= '<li>' . $comment_array[$j][$i]->question_comment . '</li>';
                }
                $comment_info .= '</ol><br/><br/>';
                $comment_info .= '</pre>';
            }
        }

        //writing the question comment table
        $this->writeHTML($comment_info, true, false, false, false, '');





        //old code that generates the other few pages.
        /*
          $this->ln();


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
          } */
    }

    //Page header
    public function Header() {
        // Logo
        $this->SetFont('helvetica', 'B', 14);
        // Title
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 14);
        //$course = $this->course->fullname."\n";
        //$this->Cell(0, 15, $this->eval->name, 0,1, L, 1, '', 0);
        //$this->Cell(0, 15, $this->eval->name, 0, 1, 'L', 0, '', 0, false, 'M', 'M');
        //  $this->SetFont('helvetica', 'B', 14);
        //  print_r($this->eval);
        //  $this->Cell(0, 15, $course, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        // // $this->Cell(0, 15, $course, 0,1, C, 1, '', 0);
        //  $this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))); 
        //  $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
        //$this->Cell(0, 15, $this->eval->name, 0,1, L, 1, '', 0);
        //$this->MultiCell(0, 5, $this->course->fullname, 1, 'C', 1, 0, '', '', true);
        //ugh multicells. ($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        $this->MultiCell(0, 5, $this->course->fullname, 1, 'C', 1, 1, '', '',
                true, 1, false, true, 10);
        //$this->ln();
        $this->MultiCell(134, 5, $this->eval->name, 1, 'L', 1, 0, '', '', true);

        $this->MultiCell(133, 5, $this->teacher->name, 1, 'R', 1, 0, '', '',
                true);
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10,
                'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
                0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function display_statistics($class, $responses_data) {

        //Load Statistics if applicable
        if ($class::is_averagable()) {
            $average = $class::average($responses_data);
            $this->MultiCell(0, 0, $average, $border = 0, $align = 'l',
                    $fill = false, $ln = 1, $x = '', $y = '', $reseth = true,
                    $stretch = 0, $ishtml = true, $autopadding = true,
                    $maxh = 0, $valign = 'T', $fitcell = false);
        }

        if ($class::is_medianable()) {
            $average = $class::median($responses_data);
            $this->MultiCell(0, 0, $average, $border = 0, $align = 'l',
                    $fill = false, $ln = 1, $x = '', $y = '', $reseth = true,
                    $stretch = 0, $ishtml = true, $autopadding = true,
                    $maxh = 0, $valign = 'T', $fitcell = false);
        }

        if ($class::is_modeable()) {
            $average = $class::mode($responses_data);
            $this->MultiCell(0, 0, $average, $border = 0, $align = 'l',
                    $fill = false, $ln = 1, $x = '', $y = '', $reseth = true,
                    $stretch = 0, $ishtml = true, $autopadding = true,
                    $maxh = 0, $valign = 'T', $fitcell = false);
        }

        if ($class::is_rangeable()) {
            $average = $class::range($responses_data);
            $this->MultiCell(0, 0, $average, $border = 0, $align = 'l',
                    $fill = false, $ln = 1, $x = '', $y = '', $reseth = true,
                    $stretch = 0, $ishtml = true, $autopadding = true,
                    $maxh = 0, $valign = 'T', $fitcell = false);
        }


        if ($class::is_count_responses()) {

            $average = $class::count_responses($responses_data);
            $this->MultiCell(0, 0, $average, $border = 0, $align = 'l',
                    $fill = false, $ln = 1, $x = '', $y = '', $reseth = true,
                    $stretch = 0, $ishtml = true, $autopadding = true,
                    $maxh = 0, $valign = 'T', $fitcell = false);
        }
    }

    public function stddev_calc($array) {
        //Don Knuth is the $deity of algorithms
        //http://en.wikipedia.org/wiki/Algorithms_for_calculating_variance#III._On-line_algorithm
        $n = 0;
        $mean = 0;
        $M2 = 0;
        foreach ($array as $x) {
            $n++;
            $delta = $x - $mean;
            $mean = $mean + $delta / $n;
            $M2 = $M2 + $delta * ($x - $mean);
        }
        $variance = $M2 / ($n - 1);
        return sqrt($variance);
    }

}

?>
