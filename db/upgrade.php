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
 * This file keeps track of upgrades to the newmodule module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 */
defined('MOODLE_INTERNAL') || die();
require_once('../local/evaluations/locallib.php');

/**
 * xmldb_newmodule_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_evaluations_upgrade($oldversion) {
    global $DB;

//check for question types and install
    update_question_types();

//make sure the role is installed
    role_install();

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes



    if ($oldversion < 2011032414) {

        // Define table evaluatons_mail_que to be created
        $table = new xmldb_table('evaluations_mail_que');

        // Adding fields to table evaluatons_mail_que
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userto', XMLDB_TYPE_INTEGER, '20', null,
                XMLDB_NOTNULL, null, null);
        $table->add_field('from_title', XMLDB_TYPE_TEXT, 'small', null, null,
                null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, 'small', null, null, null,
                null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('body_html', XMLDB_TYPE_TEXT, 'big', null, null, null,
                null);
        $table->add_field('date_queued', XMLDB_TYPE_INTEGER, '20', null, null,
                null, null);

        // Adding keys to table evaluatons_mail_que
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for evaluatons_mail_que
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // evaluations savepoint reached
        upgrade_plugin_savepoint(true, 2011032414, 'local', 'evaluations');
    }

    if ($oldversion < 2011032422) {

        // Define table evaluations_invigilators to be created
        $table = new xmldb_table('evaluations_invigilators');

        // Adding fields to table evaluations_invigilators
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evalid', XMLDB_TYPE_INTEGER, '20', null,
                XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null,
                XMLDB_NOTNULL, null, null);

        // Adding keys to table evaluations_invigilators
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for evaluations_invigilators
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // evaluations savepoint reached
        upgrade_plugin_savepoint(true, 2011032422, 'local', 'evaluations');
    }


    if ($oldversion < 2012071903) {
        //New table to be created
        $table = new xmldb_table('department_administrators');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null,
                XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_TEXT, 'small', null,
                XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for this table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        //Now update other tables.
        //Note I'm being forced to make department null by default. (Putting not null and a default value breaks new xmldb_field for a text column.)
        //[XMLDB has detected one TEXT/BINARY column (department) with some DEFAULT defined. This type of columns cannot have any default value.]
        //Which then leads to add_field failing because $field does not have a default value anymore
        //[Field evaluations->department cannot be added. Not null fields added to non empty tables require default value. Create skipped.]
        $table = new xmldb_table('evaluations');
        $field = new xmldb_field('department', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'email_students');
        $dbman->add_field($table, $field);

        $table = new xmldb_table('evaluation_standard_question');
        $field = new xmldb_field('department', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'email_students');
        $dbman->add_field($table, $field);

        upgrade_plugin_savepoint(true, 2012071903, 'local', 'evaluations');
    }

    if ($oldversion < 2012080202) {
        $table = new xmldb_table('department_preambles');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('preamble', XMLDB_TYPE_TEXT, 'big', null,
                XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_TEXT, 'small', null,
                XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2012080202, 'local', 'evaluations');
    }

    if ($oldversion < 2012080300) {

        $table = new xmldb_table('evaluation_questions');
        $field = new xmldb_field('isstd', XMLDB_TYPE_INTEGER, '1', null,
                        XMLDB_NOTNULL, null, 1, 'question_order');
        $dbman->add_field($table, $field);

        upgrade_plugin_savepoint(true, 2012080300, 'local', 'evaluations');
    }

    if ($oldversion < 2012083103) {

        $table = new xmldb_table('evaluation_compare');
        $field = new xmldb_field('evalid', XMLDB_TYPE_INTEGER, '10', null,
                        XMLDB_NOTNULL, null, 1, 'id');
        $dbman->add_field($table, $field);
        $field2 = new xmldb_field('evalids', XMLDB_TYPE_TEXT, 'big', null,
                        XMLDB_NOTNULL, null, null, 'id');
        $dbman->drop_field($table, $field2);
        upgrade_plugin_savepoint(true, 2012083103, 'local', 'evaluations');
    }

    return true;
}

