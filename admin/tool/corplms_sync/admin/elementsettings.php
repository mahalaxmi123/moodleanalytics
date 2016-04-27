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

$elementname = required_param('element', PARAM_TEXT);

if (!$element = corplms_sync_get_element($elementname)) {
    print_error('elementnotfound', 'tool_corplms_sync');
}

admin_externalpage_setup('syncelement'.$elementname);

$form = new corplms_sync_element_settings_form($FULLME, array('element'=>$element));

/// Process actions
if ($data = $form->get_data()) {
    // Set selected source
    set_config('source_'.$elementname, $data->{'source_'.$elementname}, 'corplms_sync');

    if ($element->has_config()) {
        // Save element-specific config
        $element->config_save($data);
    }

    corplms_set_notification(get_string('settingssaved', 'tool_corplms_sync'), $FULLME, array('class'=>'notifysuccess'));
}


/// Set form data
$form->set_data(get_config($element->get_classname()));


/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("settings:{$elementname}", 'tool_corplms_sync'));

$form->display();

echo $OUTPUT->footer();

