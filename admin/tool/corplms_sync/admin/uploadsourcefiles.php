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
require_once($CFG->dirroot.'/admin/tool/corplms_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/corplms_sync/admin/forms.php');

admin_externalpage_setup('uploadsyncfiles');

$form = new corplms_sync_source_files_form();

/// Process actions
$elements = corplms_sync_get_elements($onlyenabled=true);
$systemcontext = context_system::instance();
$can_upload_any = false;
foreach ($elements as $e) {
    $elementname = $e->get_name();
    if (has_capability('tool/corplms_sync:upload' . $elementname, $systemcontext)) {
        $can_upload_any = true;
        break;
    }
}

if ($data = $form->get_data()) {

    $fileaccess = get_config('corplms_sync', 'fileaccess');
    if ($fileaccess == FILE_ACCESS_UPLOAD) {
        $fs = get_file_storage();
        $readyfiles = array();
        foreach ($elements as $e) {
            $elementname = $e->get_name();
            if (!has_capability('tool/corplms_sync:upload' . $elementname, $systemcontext)) {
                continue;
            }

            //delete any existing uploaded files
            $fs->delete_area_files($systemcontext->id, 'corplms_sync', $elementname);

            //save draftfile to file directory
            if (isset($data->$elementname)) {
                $draftid = $data->$elementname;
                file_save_draft_area_files($draftid, $systemcontext->id, 'corplms_sync', $elementname, $draftid, array('subdirs' => true));
                set_config('sync_'.$elementname.'_itemid', $draftid, 'corplms_sync');
            } else {
                continue;
            }

            //delete the draftfile - at this point it should be safe to assume $USER is the uploader
            $draft_context = context_user::instance($USER->id);
            $fs->delete_area_files($draft_context->id, 'user', 'draft', $draftid);
        }
   }

    corplms_set_notification(get_string('uploadsuccess', 'tool_corplms_sync'), $FULLME,
        array('class'=>'notifysuccess'));
}


/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadsyncfiles', 'tool_corplms_sync'));

if (get_config('corplms_sync', 'fileaccess') == FILE_ACCESS_DIRECTORY) {
    $link = html_writer::link(new moodle_url('admin/tool/corplms_sync/admin/elements.php', null), get_string('uploadaccessdeniedlink', 'tool_corplms_sync'));
    print_string('uploadaccessdenied', 'tool_corplms_sync', $link);
} else if ($can_upload_any) {
    $form->display();
} else {
    // @todo error message
}

echo $OUTPUT->footer();
