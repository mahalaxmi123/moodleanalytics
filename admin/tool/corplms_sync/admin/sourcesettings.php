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
$sourcename = required_param('source', PARAM_TEXT);

admin_externalpage_setup($sourcename);

if (!$element = corplms_sync_get_element($elementname)) {
    print_error('elementnotfound', 'tool_corplms_sync');
}

$source = $element->get_source($sourcename);

if (!$source->has_config()) {
    print_error('nosourceconfig', 'tool_corplms_sync');
}

$form = new corplms_sync_source_settings_form($FULLME, array('elementname' => $elementname, 'source' => $source));

/// Process actions
if ($data = $form->get_data()) {
    // Save source-specific config
    $source->config_save($data);

    corplms_set_notification(get_string('settingssaved', 'tool_corplms_sync'), $FULLME, array('class'=>'notifysuccess'));
}

/// Set form data
$form->set_data(get_config($sourcename));

/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("settings:{$sourcename}", 'tool_corplms_sync'));

$form->display();

echo $OUTPUT->footer();

