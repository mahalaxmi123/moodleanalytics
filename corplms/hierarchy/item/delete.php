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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/corplms/hierarchy/lib.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$prefix   = required_param('prefix', PARAM_ALPHA);
$shortprefix = hierarchy::get_short_prefix($prefix);
$id     = required_param('id', PARAM_INT);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);
$page  = optional_param('page', 0, PARAM_INT);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

$item = $hierarchy->get_item($id);
if (!$item) {
    add_to_log(SITEID, $prefix, 'delete item fail', "index.php?id={$framework->id}&amp;prefix={$prefix}", "invalid hierarchy item id (ID $id)");
    print_error('noitemid', 'corplms_hierarchy');
}
// Load framework
if (!$framework = $DB->get_record($shortprefix.'_framework', array('id' => $item->frameworkid))) {
    print_error('invalidframeworkid', 'corplms_hierarchy', $prefix);
}

require_capability('corplms/hierarchy:delete'.$prefix, $sitecontext);

// Setup page and check permissions
admin_externalpage_setup($prefix.'manage','',array('prefix' => $prefix));

if (!$delete) {
    ///
    /// Display page
    ///
    $PAGE->navbar->add(get_string("{$prefix}frameworks", 'corplms_hierarchy'), new moodle_url('/corplms/hierarchy/framework/index.php', array('prefix' => $prefix)));
    $PAGE->navbar->add(format_string($framework->fullname), new moodle_url('/corplms/hierarchy/index.php', array('prefix' => $prefix, 'frameworkid' => $framework->id)));
    $PAGE->navbar->add(format_string($item->fullname), new moodle_url('/corplms/hierarchy/item/view.php', array('prefix' => $prefix, 'id' => $item->id)));
    $PAGE->navbar->add(get_string('delete'.$prefix, 'corplms_hierarchy'));

    echo $OUTPUT->header();

    $strdelete = $hierarchy->get_delete_message($item->id);

    echo $OUTPUT->confirm($strdelete, new moodle_url("/corplms/hierarchy/item/delete.php", array('prefix' => $prefix, 'id' => $item->id, 'delete' => md5($item->timemodified), 'sesskey' => $USER->sesskey, 'page' => $page)),
                                      new moodle_url("/corplms/hierarchy/index.php", array('prefix' => $prefix, 'frameworkid' => $item->frameworkid)));

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete
///
if ($delete != md5($item->timemodified)) {
    print_error('error:deletetypecheckvariable', 'corplms_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

if ($hierarchy->delete_hierarchy_item($item->id)) {

    add_to_log(SITEID, $prefix, 'delete item', "index.php?id={$framework->id}&amp;prefix={$prefix}", substr(strip_tags($item->fullname), 0, 200) . " (ID $item->id)");
    corplms_set_notification(get_string('deleted'.$prefix, 'corplms_hierarchy', format_string($item->fullname)),
        "{$CFG->wwwroot}/corplms/hierarchy/index.php?prefix=$prefix&frameworkid={$item->frameworkid}&page={$page}",
        array('class' => 'notifysuccess'));
} else {
    corplms_set_notification(get_string($prefix.'error:deletedframework', 'corplms_hierarchy', format_string($item->fullname)),
        "{$CFG->wwwroot}/corplms/hierarchy/index.php?prefix=$prefix&frameworkid={$item->frameworkid}&page={$page}");
}
