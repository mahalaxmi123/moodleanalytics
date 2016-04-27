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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package corplms
 * @subpackage corplms_plan
 */
/**
 * Displays certifications for the current user
 *
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/corplms/reportbuilder/lib.php');
require_once($CFG->dirroot . '/corplms/completionimport/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');

$importname = optional_param('importname', 'course', PARAM_ALPHA);
$timecreated = optional_param('timecreated', null, PARAM_INT);
$importuserid = optional_param('importuserid', null, PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);
$clearfilters = optional_param('clearfilters', 0, PARAM_INT);

$pageparams = array('importname' => $importname, 'clearfilters' => $clearfilters);
if (!empty($importuserid)) {
    $pageparams['importuserid'] = $importuserid;
}
if (!empty($timecreated)) {
    $pageparams['timecreated'] = $timecreated;
}

require_login();

// Check if certifications are enabled.
if ($importname === 'certification') {
    check_certification_enabled();
}

$context = context_system::instance();
$PAGE->set_context($context);

$shortname = 'completionimport_' . $importname;
if (!$report = reportbuilder_get_embedded_report($shortname, $pageparams, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'corplms_reportbuilder');
}

$pageheading = get_string('pluginname', 'corplms_completionimport');
$PAGE->set_heading(format_string($pageheading));
$PAGE->set_title(format_string($pageheading));
$PAGE->set_url('/corplms/completionimport/viewreport.php', $pageparams);
$PAGE->set_pagelayout('noblocks');
$PAGE->set_button($report->edit_button());
admin_externalpage_setup('corplms_completionimport_' . $importname);

$renderer = $PAGE->get_renderer('corplms_reportbuilder');

if ($format != '') {
    $report->export_data($format);
    die;
}

$report->include_js();

echo $OUTPUT->header();

// Standard report stuff.
echo $OUTPUT->container_start('', 'completion_import');

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$heading = $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);
if ($debug) {
    $report->debug($debug);
}
echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

echo $renderer->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$renderer->export_select($report->_id, $sid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
