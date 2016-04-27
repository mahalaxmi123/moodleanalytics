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
 * Corplms navigation page.
 *
 * @package    corplms
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@corplmslms.com>
 */

use \corplms_core\corplms\menu\menu as menu;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Actions to manage categories.
$moveup   = optional_param('moveup',   0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$hideid   = optional_param('hideid',   0, PARAM_INT);
$showid   = optional_param('showid',   0, PARAM_INT);

admin_externalpage_setup('corplmsnavigation');

$url = new moodle_url('/corplms/core/menu/index.php');
if (!empty($movedown)) {
    require_sesskey();
    menu::change_sortorder($movedown, false);
    corplms_set_notification(get_string('menuitem:movesuccess', 'corplms_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($moveup)) {
    require_sesskey();
    menu::change_sortorder($moveup, true);
    corplms_set_notification(get_string('menuitem:movesuccess', 'corplms_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($hideid)) {
    require_sesskey();
    menu::change_visibility($hideid, true);
    corplms_set_notification(get_string('menuitem:updatesuccess', 'corplms_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($showid)) {
    require_sesskey();
    menu::change_visibility($showid, false);
    corplms_set_notification(get_string('menuitem:updatesuccess', 'corplms_core'), $url, array('class' => 'notifysuccess'));
}

$event = \corplms_core\event\menuadmin_viewed::create(array('context' => \context_system::instance()));
$event->trigger();

// Display page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('corplmsnavigation', 'corplms_core'));
$editurl = new moodle_url('/corplms/core/menu/edit.php', array('id' => '0'));
echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'corplms_core'), 'get');

// Print table header.
$table = new html_table;
$table->id = 'corplmsmenu';
$table->attributes['class'] = 'admintable generaltable editcourse';

$table->head = array(
                get_string('menuitem:title', 'corplms_core'),
                get_string('menuitem:url', 'corplms_core'),
                get_string('menuitem:visibility', 'corplms_core'),
                get_string('edit'),
);
$table->colclasses = array(
                'leftalign name',
                'centeralign count',
                'centeralign icons',
                'leftalign actions'
);
$table->data = array();

$node = menu::get();
corplms_menu_table_load($table, $node);
echo html_writer::table($table);

echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'corplms_core'), 'get');
echo $OUTPUT->footer();
