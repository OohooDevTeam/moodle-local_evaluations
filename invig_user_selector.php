<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * The specialized moodle user selector.
 *
 *
 * @package   Agenda/Meeting Extension to Committee Module
 * @copyright 2011 Dustin Durand
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/user/selector/lib.php");
require_once("$CFG->dirroot/local/evaluations/locallib.php");
class invig_user_selector extends user_selector_base {

    private $course;
    private $stored;

    function __construct($name, $options) {
        $this->course = $options['course'];
        $this->stored = $options['stored'];
        parent::__construct($name, $options);
        $this->set_rows($options['rows']);
        $this->set_multiselect(true);
        $this->preserveselected = true;

    }

    function find_users($search) {
        global $DB;
//'local/evaluations:invigilator'
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $course = 4;
        $context = get_context_instance(CONTEXT_COURSE, $this->course);

        $output = array();
        
        if(!empty($this->stored)){
        $sql = "SELECT u.* FROM {user} u WHERE u.id IN ($this->stored)";
        $users = $DB->get_records_sql($sql);
        $output[get_string('current_ingiv', 'local_evaluations')] = $users;
        }
       
        
        $users = get_users_by_capability_search($context, 'local/evaluations:invigilator', $fields = '', $sort = 'u.lastname', $limitfrom = '', $limitnum = '', $groups = '', $this->exclude, $wherecondition, $params, $useviewallgroups = false);      
        
        $output[get_string('matched_ingiv_users', 'local_evaluations')] = $users;
                
        return $output;
    }

    function get_options() {
        $options = parent::get_options();
        $options['file'] = '/local/evaluations/invig_user_selector.php';
         $options['course'] = $this->course;
         $options['rows'] = $this->get_rows();
         $options['stored'] = $this->stored;
        return $options;
    }

}

