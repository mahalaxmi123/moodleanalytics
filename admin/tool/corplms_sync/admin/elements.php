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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package corplms
 * @subpackage corplms_sync
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/admin/tool/corplms_sync/lib.php');

admin_externalpage_setup('managesyncelements');

$url = $CFG->wwwroot.'/admin/tool/corplms_sync/admin/elements.php';

/// Get all elements
$elements = corplms_sync_get_elements();

/// Process actions
$systemcontext = context_system::instance();
if ($enable = optional_param('enable', null, PARAM_TEXT)) {
    require_sesskey();
    if (has_capability("tool/corplms_sync:manage{$enable}", $systemcontext) && !empty($elements[$enable])) {
        $elements[$enable]->enable();
        corplms_set_notification(get_string('elementenabled', 'tool_corplms_sync'), $url, array('class'=>'notifysuccess'));
    }
} elseif ($disable = optional_param('disable', null, PARAM_TEXT)) {
    require_sesskey();
    if (has_capability("tool/corplms_sync:manage{$disable}", $systemcontext) && !empty($elements[$disable])) {
        $elements[$disable]->disable();
        corplms_set_notification(get_string('elementdisabled', 'tool_corplms_sync'), $url, array('class'=>'notifysuccess'));
    }
}


/// Strings used often
$strenable = get_string('enable');
$strdisable = get_string('disable');
$strsettings = get_string('settings');


/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managesyncelements', 'tool_corplms_sync'));

$table = new flexible_table('admin-corplms-sync-elements');

$table->define_columns(array('name', 'enabledisable', 'settings'));
$table->define_headers(array(get_string('element', 'tool_corplms_sync'),
    $strdisable.'/'.$strenable,
    $strsettings));
$table->define_baseurl($CFG->wwwroot.'/admin/tool/corplms_sync/admin/elements.php');
$table->set_attribute('id', 'elements');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
$table->setup();

$count = count($elements);
$rownumber = 0;
foreach ($elements as $ename => $eobj) {
    if (!has_capability('tool/corplms_sync:manage' . $ename, $systemcontext)) {
        continue;
    }

    $row = array();

    $class = $eobj->is_enabled() ? '' : 'dimmed_text';

    // Element name
    $row[] = html_writer::tag('span', get_string('displayname:'.$ename, 'tool_corplms_sync'), array('class' => $class));

    // Visible/hidden
    if ($eobj->is_enabled()) {
        $row[] = $OUTPUT->action_icon(new moodle_url('/admin/tool/corplms_sync/admin/elements.php', array('disable' => $ename, 'sesskey' => $USER->sesskey)),
                            new pix_icon('i/hide', $strdisable), null, array('title' => $strdisable));
    } else {
        $row[] = $OUTPUT->action_icon(new moodle_url('/admin/tool/corplms_sync/admin/elements.php', array('enable' => $ename, 'sesskey' => $USER->sesskey)),
                            new pix_icon('i/show', $strenable), null, array('title' => $strenable));
    }
    $row[] = $eobj->is_enabled() ? html_writer::link(new moodle_url('/admin/tool/corplms_sync/admin/elementsettings.php', array('element' => $ename)), $strsettings) : '';

    if (++$rownumber >= $count) {
        $table->add_data($row, 'last');
    } else {
        $table->add_data($row);
    }
}

$table->finish_html();

$fileaccess = get_config('corplms_sync', 'fileaccess');

echo $OUTPUT->footer();
