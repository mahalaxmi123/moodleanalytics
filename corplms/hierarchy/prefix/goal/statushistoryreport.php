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
 * @author Nathan Lewis <nathan.lewis@corplmslms.com>
 * @package corplms
 * @subpackage corplms_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/corplms/reportbuilder/lib.php');
require_once($CFG->dirroot . '/corplms/hierarchy/prefix/goal/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$itemandscope = optional_param('itemandscope', null, PARAM_TEXT);
$userid = optional_param('userid', null, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$debug = optional_param('debug', 0, PARAM_INT);

if ($userid == 0) {
    $userid = null;
}
$url = new moodle_url('/corplms/hierarchy/prefix/goal/statushistoryreport.php');
$data = array();
if (!empty($userid)) {
    $data['userid'] = $userid;
}
if (!empty($itemandscope)) {
    $data['itemandscope'] = $itemandscope;
}
$url->params($data);

admin_externalpage_setup('goalreport', '', null, $url);

$renderer = $PAGE->get_renderer('corplms_reportbuilder');

if (!$report = reportbuilder_get_embedded_report('goal_status_history', $data, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'corplms_reportbuilder');
}

if ($format != '') {
    $report->export_data($format);
    die;
}

$PAGE->set_button($report->edit_button());
echo $renderer->header();

if ($debug) {
    $report->debug($debug);
}

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$heading = get_string('goalstatushistoryreportfor', 'corplms_hierarchy');
$heading .= $renderer->print_result_count_string($countfiltered, $countall);
echo $renderer->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

$report->include_js();

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $renderer->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$renderer->export_select($report->_id, $sid);

echo $renderer->footer();
