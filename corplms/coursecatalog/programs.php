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
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @package corplms
 * @subpackage coursecatalog
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/corplms/reportbuilder/lib.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');

$debug = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_corplms_menu_selected('program');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_url('/corplms/coursecatalog/programs.php');
if ($CFG->forcelogin) {
    require_login();
}

check_program_enabled();

$renderer = $PAGE->get_renderer('corplms_reportbuilder');
$strheading = get_string('searchprograms', 'corplms_program');
$shortname = 'catalogprograms';

if (!$report = reportbuilder_get_embedded_report($shortname, null, false, 0)) {
    print_error('error:couldnotgenerateembeddedreport', 'corplms_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();

\corplms_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = get_string('programs', 'corplms_program');
$pagetitle = format_string(get_string('findlearning', 'corplms_core') . ': ' . $fullname);

$PAGE->navbar->add($fullname, new moodle_url('/corplms/coursecatalog/programs.php'));
$PAGE->navbar->add(get_string('search'));
$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

if ($debug) {
    $report->debug($debug);
}

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$heading = $strheading . ': ' .
    $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

print $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

$report->display_table();

echo $OUTPUT->footer();
