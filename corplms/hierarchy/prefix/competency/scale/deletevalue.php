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
 * @author Simon Coggins <simon.coggins@corplmslms.com>
 * @package corplms
 * @subpackage corplms_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/corplms/hierarchy/lib.php');
require_once('lib.php');


///
/// Setup / loading data
///

// Get params
$id = required_param('id', PARAM_INT);
$prefix = required_param('prefix', PARAM_ALPHA);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

// Cache user capabilities.
$sitecontext = context_system::instance();

// Permissions.
require_capability('corplms/hierarchy:deletecompetencyscale', $sitecontext);

// Set up the page.
admin_externalpage_setup($prefix.'manage');

if (!$value = $DB->get_record('comp_scale_values', array('id' => $id))) {
    print_error('incorrectcompetencyscalevalueid', 'corplms_hierarchy');
}

$scale = $DB->get_record('comp_scale', array('id' => $value->scaleid));

///
/// Display page
///

$returnparams = array('id' => $value->scaleid, 'prefix' => 'competency');
$returnurl = new moodle_url('/corplms/hierarchy/prefix/competency/scale/view.php', $returnparams);
$deleteparams = array('id' => $value->id, 'delete' => md5($value->timemodified), 'sesskey' => $USER->sesskey, 'prefix' => 'competency');
$deleteurl = new moodle_url('/corplms/hierarchy/prefix/competency/scale/deletevalue.php', $deleteparams);

// Can't delete if the scale is in use
if (competency_scale_is_used($value->scaleid)) {
    corplms_set_notification(get_string('error:nodeletescalevalueinuse', 'corplms_hierarchy'), $returnurl);
}

if ($value->id == $scale->defaultid) {
    corplms_set_notification(get_string('error:nodeletecompetencyscalevaluedefault', 'corplms_hierarchy'), $returnurl);
}

if (!$delete) {
    echo $OUTPUT->header();
    $strdelete = get_string('deletecheckscalevalue', 'corplms_hierarchy');

    echo $OUTPUT->confirm($strdelete . html_writer::empty_tag('br') . html_writer::empty_tag('br') . format_string($value->name), $deleteurl, $returnurl);

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete competency scale
///

if ($delete != md5($value->timemodified)) {
    corplms_set_notification(get_string('error:checkvariable', 'corplms_hierarchy'), $returnurl);
}

if (!confirm_sesskey()) {
    corplms_set_notification(get_string('confirmsesskeybad', 'error'), $returnurl);
}

$DB->delete_records('comp_scale_values', array('id' => $value->id));

add_to_log(SITEID, 'competency', 'delete scale value', "prefix/competency/scale/view.php?id={$value->scaleid}&amp;prefix=competency", "$value->name (ID $value->id)");

corplms_set_notification(get_string('deletedcompetencyscalevalue', 'corplms_hierarchy', format_string($value->name)), $returnurl, array('class' => 'notifysuccess'));
