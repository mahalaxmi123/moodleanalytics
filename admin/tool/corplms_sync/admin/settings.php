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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/corplms_sync/admin/forms.php");
require_once($CFG->dirroot . '/corplms/core/lib/scheduler.php');

admin_externalpage_setup('corplmssyncsettings');

$form = new corplms_sync_config_form();

// Process actions.
if ($data = $form->get_data()) {
    // File access.
    if (isset($data->fileaccess)) {
        set_config('fileaccess', $data->fileaccess, 'corplms_sync');
    }
    if (isset($data->filesdir)) {
        set_config('filesdir', trim($data->filesdir), 'corplms_sync');
    }

    // Notifications.
    set_config('notifymailto', $data->notifymailto, 'corplms_sync');

    $notifytypes = !empty($data->notifytypes) ? implode(',', array_keys($data->notifytypes)) : '';
    set_config('notifytypes', $notifytypes, 'corplms_sync');

    // Schedule.
    set_config('cronenable', $data->cronenable, 'corplms_sync');
    if ($data->cronenable) {
        set_config('frequency', $data->frequency, 'corplms_sync');
        set_config('schedule', $data->schedule, 'corplms_sync');
        // Reset next sync time.
        $scheduler = new scheduler($data, array('nextevent' => 'nextcron'));
        $scheduler->next();
        set_config('nextcron', $scheduler->get_scheduled_time(), 'corplms_sync');
    }

    corplms_set_notification(get_string('settingssaved', 'tool_corplms_sync'), $PAGE->url, array('class'=>'notifysuccess'));
}


// Set form data.
$config = get_config('corplms_sync');
if (!empty($config->notifytypes)) {
    $config->notifytypes = explode(',', $config->notifytypes);
    foreach ($config->notifytypes as $index => $issuetype) {
        $config->notifytypes[$issuetype] = 1;
        unset($config->notifytypes[$index]);
    }
}
$form->set_data($config);

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('generalsettings', 'tool_corplms_sync'));

$form->display();

echo $OUTPUT->footer();
