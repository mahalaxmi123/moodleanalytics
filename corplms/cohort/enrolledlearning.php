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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package corplms
 * @subpackage cohort
 */
/**
 * This file displays the embedded report to show the "enrolled learning" items for a single cohort
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/corplms/reportbuilder/lib.php');
require_once($CFG->dirroot . '/corplms/core/js/lib/setup.php');

$context = context_system::instance();

$id     = optional_param('id', false, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', false, PARAM_BOOL);

$PAGE->set_context($context);

$url = new moodle_url('/corplms/cohort/enrolledlearning.php', array('id' => $id, 'format' => $format, 'debug' => $debug));
admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout' => 'report'));

if (!$id) {
    echo $OUTPUT->header();
    $url = new moodle_url('/cohort/index.php');
    echo $OUTPUT->container(get_string('cohortenrolledlearningselect', 'corplms_cohort', $url->out()));
    echo $OUTPUT->footer();
    exit;
}

$report = reportbuilder_get_embedded_report('cohort_associations_enrolled', array('cohortid' => $id), false, $sid);

// Handle a request for export
if ($format != '') {
    $report->export_data($format);
    die;
}

\corplms_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

// Setup lightbox.
local_js(
    array(
        CORPLMS_JS_DIALOG,
        CORPLMS_JS_TREEVIEW,
        CORPLMS_JS_DATEPICKER
    )
);
// Include cohort learning js module.
$PAGE->requires->strings_for_js(array('none'), 'moodle');
$PAGE->requires->strings_for_js(array('assignenrolledlearning', 'deletelearningconfirm', 'savinglearning'), 'corplms_cohort');
$jsmodule = array(
        'name' => 'corplms_cohortlearning',
        'fullpath' => '/corplms/cohort/dialog/learningitem.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"COHORT_ASSN_ITEMTYPE_PROGRAM":' . COHORT_ASSN_ITEMTYPE_PROGRAM . ','.
        '"COHORT_ASSN_ITEMTYPE_COURSE":' . COHORT_ASSN_ITEMTYPE_COURSE . ','.
        '"COHORT_ASSN_VALUE_VISIBLE":' . COHORT_ASSN_VALUE_VISIBLE .','.
        '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_value":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_string":"' . $COHORT_ASSN_VALUES[COHORT_ASSN_VALUE_ENROLLED] .'",'.
        '"saveurl":"/corplms/cohort/enrolledlearning.php" }');
$PAGE->requires->js_init_call('M.corplms_cohortlearning.init', $args, false, $jsmodule);
// Include cohort programcompletion js module
$PAGE->requires->strings_for_js(array('datepickerlongyeardisplayformat', 'datepickerlongyearplaceholder', 'datepickerlongyearregexjs'), 'corplms_core');
$PAGE->requires->strings_for_js(array('completioncriteria', 'pleaseentervaliddate',
    'pleaseentervalidunit', 'pleasepickaninstance', 'chooseitem', 'removecompletiondate'), 'corplms_program');
$selected_program = json_encode(dialog_display_currently_selected(get_string('selected', 'corplms_hierarchy'), 'program-completion-event-dialog'));
$jsmodule = array(
        'name' => 'corplms_cohortprogramcompletion',
        'fullpath' => '/corplms/cohort/dialog/programcompletion.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"selected_program":'.$selected_program.','.
        '"COMPLETION_EVENT_NONE":'.COMPLETION_EVENT_NONE.','.
        '"COMPLETION_TIME_NOT_SET":'.COMPLETION_TIME_NOT_SET.','.
        '"COMPLETION_EVENT_FIRST_LOGIN":'.COMPLETION_EVENT_FIRST_LOGIN.','.
        '"COMPLETION_EVENT_ENROLLMENT_DATE":'.COMPLETION_EVENT_ENROLLMENT_DATE.'}');
$PAGE->requires->js_init_call('M.corplms_cohortprogramcompletion.init', $args, false, $jsmodule);

$strheading = get_string('enrolledlearning', 'corplms_cohort');
corplms_cohort_navlinks($cohort->id, $cohort->name, $strheading);
echo $OUTPUT->header();

if ($debug) {
    $report->debug($debug);
}

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('enrolledlearning', $cohort->id, $cohort->cohorttype, $cohort);

echo html_writer::start_tag('div', array('class' => 'buttons'));

// add courses
echo html_writer::start_tag('div', array('class' => 'singlebutton'));
echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-course-learningitem-dialog',
    'value' => get_string('addcourses', 'corplms_cohort')));
echo html_writer::end_tag('div');

// add programs
if (corplms_feature_visible('programs')) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-program-learningitem-dialog',
        'value' => get_string('addprograms', 'corplms_cohort')));
    echo html_writer::end_tag('div');
}
echo html_writer::end_tag('div');

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$report->display_table();

$output = $PAGE->get_renderer('corplms_reportbuilder');
$output->export_select($report->_id, $sid);

echo $OUTPUT->footer();
