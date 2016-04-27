<?php
/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2010 onwards Corplms Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @package corplms
 * @subpackage plan
 */

/**
 * Functions for creating/processing the settings form for this component
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// Include main component class
require_once($CFG->dirroot.'/corplms/plan/components/competency/competency.class.php');


/**
 * Build settings form for configurating this component
 *
 * @access  public
 * @param   object  $mform  Moodle form object
 * @param   array $customdata mform customdata
 * @return  void
 */
function dp_competency_component_build_settings_form(&$mform, $customdata) {
    global $CFG, $DP_AVAILABLE_ROLES, $DB;

    $mform->addElement('header', 'competencysettings', get_string('competencysettings', 'corplms_plan'));
    $mform->addHelpButton('competencysettings', 'advancedsettingscompetencysettings', 'corplms_plan', '', true);

    if ($templatesettings = $DB->get_record('dp_competency_settings', array('templateid' => $customdata['id']))) {
        $defaultduedatesmode = $templatesettings->duedatemode;
        $defaultprioritymode = $templatesettings->prioritymode;
        $defaultpriorityscale = $templatesettings->priorityscale;
        $defaultautoassignpos = $templatesettings->autoassignpos;
        $defaultautoassignorg = $templatesettings->autoassignorg;
        $defaultincludecompleted = $templatesettings->includecompleted;
        $defaultautoassigncourses = $templatesettings->autoassigncourses;
        $defaultautoadddefaultevidence = $templatesettings->autoadddefaultevidence;
    } else {
        $defaultduedatesmode = null;
        $defaultprioritymode = null;
        $defaultpriorityscale = null;
        $defaultautoassignpos = null;
        $defaultautoassignorg = null;
        $defaultincludecompleted = 1;
        $defaultautoassigncourses = null;
        $defaultautoadddefaultevidence = null;
    }
    // due date mode options
    $radiogroup = array();
    $radiogroup[] =& $mform->createElement('radio', 'duedatemode', '', get_string('none', 'corplms_plan'), DP_DUEDATES_NONE);
    $radiogroup[] =& $mform->createElement('radio', 'duedatemode', '', get_string('optional', 'corplms_plan'), DP_DUEDATES_OPTIONAL);
    $radiogroup[] =& $mform->createElement('radio', 'duedatemode', '', get_string('required', 'corplms_plan'), DP_DUEDATES_REQUIRED);
    $mform->addGroup($radiogroup, 'duedategroup', get_string('duedates', 'corplms_plan'), html_writer::empty_tag('br'), false);
    $mform->setDefault('duedatemode', $defaultduedatesmode);

    // priorities mode options
    $radiogroup = array();
    $radiogroup[] =& $mform->createElement('radio', 'prioritymode', '', get_string('none', 'corplms_plan'), DP_PRIORITY_NONE);
    $radiogroup[] =& $mform->createElement('radio', 'prioritymode', '', get_string('optional', 'corplms_plan'), DP_PRIORITY_OPTIONAL);
    $radiogroup[] =& $mform->createElement('radio', 'prioritymode', '', get_string('required', 'corplms_plan'), DP_PRIORITY_REQUIRED);
    $mform->addGroup($radiogroup, 'prioritygroup', get_string('priorities', 'corplms_plan'), html_writer::empty_tag('br'), false);
    $mform->setDefault('prioritymode', $defaultprioritymode);

    // priority scale selector
    $prioritymenu = array();
    if ($priorities = dp_get_priorities()) {
        foreach ($priorities as $priority) {
            $prioritymenu[$priority->id] = $priority->name;
        }
    } else {
        $mform->addElement('static', 'nopriorityscales', null, get_string('nopriorityscales', 'corplms_plan'));
        $mform->addElement('hidden', 'disabled', 'yes');
    }

    $mform->disabledIf('prioritygroup', 'disabled', 'eq', 'yes');
    $mform->disabledIf('priorityscale', 'disabled', 'eq', 'yes');

    $mform->addElement('select', 'priorityscale', get_string('priorityscale', 'corplms_plan'), $prioritymenu);
    $mform->disabledIf('priorityscale', 'prioritymode', 'eq', DP_PRIORITY_NONE);
    if (!empty($customdata['templateinuse'])) {
        $mform->addElement('static', 'priorityscalesdisabledtemplateinuse', null, get_string('priorityscalesdisabledtemplateinuse', 'corplms_plan'));
        $mform->disabledIf('priorityscale', 'prioritymode', 'neq', -777);
    }
    $mform->setDefault('priorityscale', $defaultpriorityscale);

    // auto assign options
    $autoassigngroup = array();
    $autoassigngroup[] =& $mform->createElement('advcheckbox', 'autoassignpos', null, get_string('autoassignpos', 'corplms_plan'));
    $autoassigngroup[] =& $mform->createElement('advcheckbox', 'autoassignorg', null, get_string('autoassignorg', 'corplms_plan'));
    $autoassigngroup[] =& $mform->createElement('advcheckbox', 'includecompleted', null, get_string('includecompleted', 'corplms_plan'));
    $autoassigngroup[] =& $mform->createElement('advcheckbox', 'autoassigncourses', null, get_string('autoassigncourses', 'corplms_plan'));

    $mform->addGroup($autoassigngroup, 'autoassign', get_string('autoassign', 'corplms_plan'), array(html_writer::empty_tag('br')), false);
    $mform->setDefault('autoassignpos', $defaultautoassignpos);
    $mform->setDefault('autoassignorg', $defaultautoassignorg);
    $mform->setDefault('includecompleted', $defaultincludecompleted);
    $mform->setDefault('autoassigncourses', $defaultautoassigncourses);

    $mform->addElement('advcheckbox', 'autoadddefaultevidence', get_string('defaultstatus', 'corplms_plan'), get_string('setdefaultstatus', 'corplms_plan'), null, array(0,1));
    $mform->setDefault('autoadddefaultevidence', $defaultautoadddefaultevidence);


    //Permissions
    $mform->addElement('header', 'competencypermissions', get_string('competencypermissions', 'corplms_plan'));
    $mform->addHelpButton('competencypermissions', 'advancedsettingscompetencypermissions', 'corplms_plan', '', true);

    dp_add_permissions_table_headings($mform);

    foreach (dp_competency_component::$permissions as $action => $requestable) {
        dp_add_permissions_table_row($mform, $action, get_string($action, 'corplms_plan'), $requestable);
    }

    foreach (dp_competency_component::$permissions as $action => $requestable) {
        foreach ($DP_AVAILABLE_ROLES as $role) {
            $sql = "SELECT value FROM {dp_permissions}
                WHERE role = ? AND component = ? AND action = ? AND templateid = ?";
            $params = array($role, 'competency', $action, $customdata['id']);
            $defaultvalue = $DB->get_field_sql($sql, $params);
            $mform->setDefault($action.$role, $defaultvalue);
        }
    }
    $mform->addElement('html', html_writer::end_tag('table') . html_writer::end_tag('div'));
}


/**
 * Process settings form for configurating this component
 *
 * @access  public
 * @param   object  $fromform   Submitted form's content
 * @param   integer $id         Template ID
 * @return  void
 */
function dp_competency_component_process_settings_form($fromform, $id) {

    global $CFG, $DP_AVAILABLE_ROLES, $DB;

    $currenturl = new moodle_url('/corplms/plan/template/advancedworkflow.php', array('id' => $id, 'component' => 'competency'));


    $transaction = $DB->start_delegated_transaction();

    $currentworkflow = $DB->get_field('dp_template', 'workflow', array('id' => $id));
    if ($currentworkflow != 'custom') {
        $template_update = new stdClass();
        $template_update->id = $id;
        $template_update->workflow = 'custom';
        $DB->update_record('dp_template', $template_update);
    }
    $todb = new stdClass();
    $todb->templateid = $id;
    $todb->duedatemode = $fromform->duedatemode;
    $todb->prioritymode = $fromform->prioritymode;
    if (($fromform->prioritymode != DP_PRIORITY_NONE) && isset($fromform->priorityscale)) {
        $todb->priorityscale = $fromform->priorityscale;
    }
    $todb->autoassignorg = $fromform->autoassignorg;
    $todb->autoassignpos = $fromform->autoassignpos;
    $todb->includecompleted = $fromform->includecompleted;
    $todb->autoassigncourses = $fromform->autoassigncourses;
    $todb->autoadddefaultevidence = $fromform->autoadddefaultevidence;
    if ($competencysettings = $DB->get_record('dp_competency_settings', array('templateid' => $id))) {
        // update
        $todb->id = $competencysettings->id;
        $DB->update_record('dp_competency_settings', $todb);
    } else {
        // insert
        $DB->insert_record('dp_competency_settings', $todb);
    }
    foreach (dp_competency_component::$permissions as $action => $requestable) {
        foreach ($DP_AVAILABLE_ROLES as $role) {
            $permission_todb = new stdClass();
            $permission_todb->templateid = $id;
            $permission_todb->component = 'competency';
            $permission_todb->action = $action;
            $permission_todb->role = $role;
            $temp = $action . $role;
            $permission_todb->value = $fromform->$temp;
            $sql = "SELECT id FROM {dp_permissions}
                WHERE templateid = ? AND component = ? AND action = ? AND role = ?";
            $params = array($id, 'competency', $action, $role);
            if ($permission_setting_id = $DB->get_field_sql($sql, $params, IGNORE_MISSING)) {
                //update
                $permission_todb->id = $permission_setting_id;
                $DB->update_record('dp_permissions', $permission_todb);
            } else {
                //insert
                $DB->insert_record('dp_permissions', $permission_todb);
            }
        }
    }
    $transaction->allow_commit();
    add_to_log(SITEID, 'plan', 'changed workflow', "template/workflow.php?id={$id}", "Template ID:{$id}");
    corplms_set_notification(get_string('update_competency_settings', 'corplms_plan'), $currenturl, array('class' => 'notifysuccess'));
}
