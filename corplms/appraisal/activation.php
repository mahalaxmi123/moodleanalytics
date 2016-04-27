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
 * @subpackage corplms_appraisal
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/corplms/appraisal/lib.php');
require_once($CFG->dirroot . '/corplms/appraisal/lib/assign/lib.php');
require_once($CFG->dirroot . '/corplms/appraisal/appraisal_forms.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);

admin_externalpage_setup('manageappraisals');
$systemcontext = context_system::instance();
require_capability('corplms/appraisal:manageactivation', $systemcontext);

$output = $PAGE->get_renderer('corplms_appraisal');

$appraisal = new appraisal($id);

switch ($action) {
    case 'activate':
        list($errors, $warnings) = $appraisal->validate();
        if (empty($errors) && $confirm) {
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            $appraisal->activate();
            corplms_set_notification(get_string('appraisalactivated', 'corplms_appraisal', $appraisal->name),
                         new moodle_url('/corplms/appraisal/manage.php'), array('class' => 'notifysuccess'));
        }
        break;
    case 'close':
        $appraisal->alertbody = get_string('closealertbodydefault', 'corplms_appraisal', $appraisal);
        $appraisal->alertbodyformat = FORMAT_HTML;
        $appraisal = file_prepare_standard_editor($appraisal, 'alertbody', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context']);
        $form = new appraisal_close_form(null, array('appraisal' => $appraisal), 'post', '', null, true, 'appraisalclose');
        if ($form->is_submitted()) {
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            $formdata = $form->get_data();
            if ($formdata) {
                $appraisal->close($formdata);
                if (isset($formdata->sendalert) && $formdata->sendalert) {
                    corplms_set_notification(get_string('appraisalclosedalertssent', 'corplms_appraisal', $appraisal->name),
                                 new moodle_url('/corplms/appraisal/manage.php'), array('class' => 'notifysuccess'));
                } else {
                    corplms_set_notification(get_string('appraisalclosed', 'corplms_appraisal', $appraisal->name),
                                 new moodle_url('/corplms/appraisal/manage.php'), array('class' => 'notifysuccess'));
                }
            } else {
                redirect(new moodle_url('/corplms/appraisal/manage.php'));
            }
        }
        break;
}

echo $output->header();

switch ($action) {
    case 'activate':
        echo $output->heading($appraisal->name);
        echo $output->confirm_appraisal_activation($appraisal, $errors, $warnings);
        break;
    case 'close':
        echo $output->heading(get_string('closeappraisal', 'corplms_appraisal'));
        echo $output->confirm_appraisal_close($appraisal->count_incomplete_userassignments());
        $form->display();
        break;
    default:
        echo $output->heading($appraisal->name);
        echo get_string('unrecognizedaction', 'corplms_appraisal');
}

echo $output->footer();
