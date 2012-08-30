<?php

/**
 * *********************************************************************** *
 * *                              Evaluation                             * *
 * *********************************************************************** *
 * @package     local                                                    * *
 * @subpackage  Evaluation                                               * *
 * @name        Evaluation                                               * *
 * @copyright   oohoo.biz                                                * *
 * @link        http://oohoo.biz                                         * *
 * @author      Dustin Durrand           				 * *
 * @author      (Modified By) James Ward   				 * *
 * @author      (Modified By) Andrew McCann				 * *
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later * *
 * *********************************************************************** *
 * **********************************************************************  */

/**
 * This class handles all things related to creating and updating evaluations.
 */
class evaluation {

    private $questionSet = array(); //Evaluation questions.
    private $eval_id = 0; //The id of the evaluation or 0 if it's new.
    private $deleted = 0; //Whether or not the evaluation has been deleted.
    private /* int */ $course; //The course id of the course that the evaluation belongs to. 
    private $start_time; //The start time of the evaluation.
    private $end_time; //The end time of the evaluation.
    private $name; //The name given to the evaluation.
    private $email_students; //Whether or not to email students to inform them of the evaluation.
    private $complete = 0; //Whether or not the evaluation is complete.
    private /* string */$dept; //The department code of the department that the evaluation's course belongs to.

    function __construct($dept, $eval_id = 0, $questions = array(),
            $course = null, $start_time = null, $end_time = null, $name = null,
            $email_students = null, $complete = 0, $db_load = true) {
        global $DB;

        $this->dept = $dept; //Set the department.

        if ($eval_id != 0) {//Not a new evaluation
            if ($db_load && $eval_db = $DB->get_record('evaluations',
                    array('id' => $eval_id, 'deleted' => 0))) { //load from database
                $this->eval_id = $eval_id;
                $this->course = $eval_db->course;
                $this->start_time = $eval_db->start_time;
                $this->end_time = $eval_db->end_time;
                $this->name = $eval_db->name;
                $this->email_students = $eval_db->email_students;
                $this->complete = $eval_db->complete;
                $this->deleted = $eval_db->deleted;
            } elseif (!$db_load && $eval_db = $DB->get_record('evaluations',
                    array('id' => $eval_id, 'deleted' => 0))) { //load from parameters, BUT check if exist
                $this->eval_id = $eval_id;
                $this->course = $course;
                $this->start_time = $start_time;
                $this->end_time = $end_time;
                $this->name = $name;
                $this->email_students = $email_students;
                $this->complete = $complete;
            } else {
                print_error(get_string('eval_id_invalid', 'local_evaluations'));
            }

            $this->load_questionSet($questions);
            return;
        } else {
            //If it is a new evaluation.
            $this->eval_id = $eval_id;
            $this->course = $course;
            $this->start_time = $start_time;
            $this->end_time = $end_time;
            $this->name = $name;
            $this->email_students = $email_students;
            $this->complete = $complete;

            if (empty($questions)) {
                $this->load_standard_questionSet();
            } else {
                $this->load_questionSet($questions);
            }
        }
    }

    /**
     * Returns the id of this evaluation.
     * 
     * @return int  The id of this evaluation.
     */
    public function get_id() {
        return $this->eval_id;
    }

    /**
     * Get the course id of the course that this evaluation belongs to.
     */
    function get_course() {
        return $this->course;
    }

    /**
     * Load all standard questions into this evaluations question set.
     * 
     * @global moodle_database $DB
     * @global $CFG
     */
    function load_standard_questionSet() {
        global $DB, $CFG;
        //Get all question types.
        $question_types = $DB->get_records('evaluations_question_types');

        //Find all standard questions for this department.
        $default_questions = $DB->get_records_select('evaluation_standard_question',
                'department = \'' . $this->dept . '\'', null,
                'question_order ASC');

        //Go through each std Question and create a new question object.
        foreach ($default_questions as $order => $default_question) {

            //Get the question type's class.
            $type = $question_types[$default_question->type];
            $question_class = 'question_' . $type->class;

            //Load the question type page if not already loaded.
            require_once("$CFG->dirroot/local/evaluations/classes/question_types/$question_class.php");

            if (!class_exists($question_class)) {
                print_error(get_string('error_question_type',
                                'local_evaluations'));
            }

            //Generate a new question with the question type's class and put it into the question set at the correct place.
            $this->questionSet[$order] = new $question_class(true, 0, $default_question->question, $default_question->type, $default_question->question_order, false);
        }
    }

    /**
     * Load the passed in questions into the evaluations question set.
     * 
     * @global moodle_database $DB
     * @global $CFG
     * @param stdClass[] $questions A set of questions with the following properties:
     *          'id' -> The id of the question if it already exists otherwise 0.
     *          'question' -> The question text.
     *          'type' -> The type of quetsion that it is.
     *          'question_order' -> The order that the question will be displayed in. (0 - n) 
     *          'isstd' -> Whether or not the question is a standard question or not.
     */
    function load_questionSet($questions) {
        global $DB, $CFG;


        $question_types = $DB->get_records('evaluations_question_types');


        if (empty($questions) && $this->eval_id != 0) {
            //if no questions included, and eval is not new - load from database
            if (!$questions = $DB->get_records('evaluation_questions',
                    array('evalid' => $this->eval_id), 'question_order ASC')) {
                //If there are no questions to be loaded then create an empty question array.
                $questions = array();
            }
        }

        foreach ($questions as $order => $question) {

            //Make sure it's not a new question that's empty
            if ($question->id == 0 && $question->question == '')
                continue;

            //Get the question type's class.
            $type = $question_types[$question->type];
            $question_class = 'question_' . $type->class;

            //Load the question type page if not already loaded.
            require_once("$CFG->dirroot/local/evaluations/classes/question_types/$question_class.php");

            if (!class_exists($question_class)) {
                print_error(get_string('error_question_type',
                                'local_evaluations'));
            }

            //Generate a new question with the question type's class and put it into the question set at the correct place.
            $this->questionSet[$order] = new $question_class($question->isstd, $question->id, $question->question, $question->type, $question->question_order, false);
        }
    }

    /**
     * Not sure yet. Will fill in when I come across this method later.
     * 
     * @global moodle_database $DB
     * @param $mform
     * @param type $form
     * @param type $data
     * @param type $include_questions
     */
    function load_creation_form(&$mform, $form, $data, $include_questions = true) {
        global $DB;

        //DATA
        $data->eval_complete = $this->complete;
        $data->eval_name = $this->name;
        $data->eval_course_id = $this->course;
        $data->student_email_toogle = $this->email_students;
        $data->eval_time_start = $this->start_time;
        $data->eval_time_start_display = $this->start_time; //only for limited
        $data->eval_time_end = $this->end_time;
        $data->eval_id = $this->eval_id;

        if ($include_questions) { // If we are going to include questions - prepare forum and load their data
            $repeatarray = questionCreation_mform($mform);

            $repeatno = count($this->questionSet);
            $repeatno += 1;

            $repeateloptions = array();
            $form->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                    'option_repeats', 'option_add_fields', 1);



            foreach ($this->questionSet as $question) {
                $question->load_creation_form($form, $data);
            }
        }//end questions


        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                        get_string('complete', 'local_evaluations'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Saves any changes made to the evaluation object.
     */
    function save() {
        global $DB;
        $eval = new stdClass();

        if ($this->name == '') {
            $this->name = get_string('no_name', 'local_evaluations');
        }

        $eval->id = $this->eval_id;
        $eval->department = $this->dept;
        $eval->course = $this->course;
        $eval->start_time = $this->start_time;
        $eval->end_time = $this->end_time;
        $eval->name = $this->name;
        $eval->email_students = $this->email_students;
        $eval->complete = $this->complete;
        $eval->deleted = 0;


        if ($this->eval_id == 0) { //new eval
            unset($eval->id);


            //if start time is before now, the evaluation should start now, and not in the past
            if ($this->start_time < time()) {
                $current = time();
                $this->start_time = $current;
                $eval->start_time = $current;
            }
            //Insert Record and update evalid
            $this->eval_id = $DB->insert_record('evaluations', $eval);

            //Update trigger... I have no idea what triggers acctually do yet.
            if ($this->eval_id > 0) {

                $eventdata = new object();
                $eventdata->component = 'local/evaluations';
                $eventdata->name = 'eval_created';
                $eventdata->eval_id = $this->eval_id;
                $eventdata->course = $this->course;
                $eventdata->start_time = $this->start_time;
                $eventdata->end_time = $this->end_time;
                $eventdata->name = $this->name;
                $eventdata->email_students = $this->email_students;
                $eventdata->type = $this->type;

                events_trigger('eval_created', $eventdata);
            }
        } else {//update existing eval
            $DB->update_record('evaluations', $eval);
        }

        //Save each question.
        $this->save_questions();
    }

    /**
     * Save each question in the questionset.
     */
    function save_questions() {

        //Can't save questions to a new evaluation.
        if ($this->eval_id == 0) {
            print_error(get_string('invalid_evalid_save', 'local_evaluations'));
        }

        foreach ($this->questionSet as $question) {
            $question->save($this->eval_id);
        }
    }

    function load_display_form(&$mform, $form, $data) {
        global $dept, $USER;

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (is_dept_admin($dept, $USER)) {
            $mform->addElement('html',
                    '<h3><font color=red>PREVIEW ONLY - WILL NOT BE RECORDED</font></h3>');
        }

        $order = 1;
        foreach ($this->questionSet as $question) {
            $question->display($mform, $form, $data, $order);
            $order++;
        }

        $mform->addElement('hidden', "eval_id", $this->eval_id);

        if (is_dept_admin($dept, $USER)) {
            $mform->closeHeaderBefore('end_preview');
            $mform->addElement('html',
                    '<h3 name="end_preview" id="end_preview"><font color=red>PREVIEW ONLY - WILL NOT BE RECORDED</font></h3>');
        } else {
            $form->add_action_buttons(false);
        }
    }

}

?>
