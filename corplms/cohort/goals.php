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
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @author David Curry <david.curry@corplmslms.com>
 * @package corplms
 * @subpackage cohort
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/corplms/cohort/cohort_forms.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/corplms/core/js/lib/setup.php');
require_once($CFG->dirroot . '/corplms/hierarchy/prefix/goal/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$context = context_system::instance();
require_capability('moodle/cohort:view', $context);
$can_edit = has_capability('corplms/hierarchy:managegoalassignments', $context)
    && has_capability('moodle/cohort:manage', $context);

// Raise timelimit as this could take a while for big cohorts.
set_time_limit(0);
raise_memory_limit(MEMORY_HUGE);

define('COHORT_HISTORY_PER_PAGE', 50);

admin_externalpage_setup('cohorts');

$id = required_param('id', PARAM_INT);

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url('/corplms/cohort/learningplan.php', array('id' => $id));

// Javascript include.
local_js(
    array(
        CORPLMS_JS_DIALOG,
        CORPLMS_JS_TREEVIEW,
    )
);

$PAGE->requires->strings_for_js(array('addgoal', 'assigngoals'), 'corplms_hierarchy');
$PAGE->requires->strings_for_js(array('continue', 'cancel'), 'moodle');
$args = array('args' => '{"id":"' . $cohort->id . '",'
                       . '"sesskey":"' . sesskey() . '"}');
$jsmodule = array(
        'name' => 'corplms_cohort',
        'fullpath' => '/corplms/core/js/cohort.js',
        'requires' => array('json', 'corplms_core'));
$PAGE->requires->js_init_call('M.corplms_cohort.init',
    $args, false, $jsmodule);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('goals', $cohort->id, $cohort->cohorttype, $cohort);

// Goals header.
echo $OUTPUT->heading(get_string('goalsassigned', 'corplms_hierarchy'), 3);

// Add goal(s) button.
$button_form = '';
if ($can_edit) {
    // Needs to be done manually (not with single_button) to get correct ID on input button element.
    $add_button_text = get_string('addgoal', 'corplms_hierarchy');
    $add_goal_url = new moodle_url('/corplms/hierarchy/prefix/goal/assign/find.php', array('assignto' => $cohort->id));
    $button_form .= html_writer::start_tag('div', array('class' => 'buttons'));
    $button_form .= html_writer::start_tag('div', array('class' => 'singlebutton'));
    $button_form .= html_writer::start_tag('form', array('action' => $add_goal_url, 'method' => 'get'));
    $button_form .= html_writer::start_tag('div');
    $button_form .= html_writer::empty_tag('input', array('type' => 'submit',
        'id' => "show-assignedgoals-dialog", 'value' => $add_button_text));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "assignto", 'value' => $cohort->id));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "assigntype", 'value' => GOAL_ASSIGNMENT_AUDIENCE));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "nojs", 'value' => '1'));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "returnurl", 'value' => qualified_me()));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "s", 'value' => sesskey()));
    $button_form .= html_writer::end_tag('div');
    $button_form .= html_writer::end_tag('form');
    $button_form .= html_writer::end_tag('div');
    $button_form .= html_writer::end_tag('div');
}
echo $button_form;

// Goals Table.
$goal_table = cohort::display_goal_table($cohort, $can_edit);
echo $goal_table;

echo $OUTPUT->footer();

function display_yes_no($value) {
    return (isset($value) && $value == 1) ? get_string('yes') : get_string('no');
}
