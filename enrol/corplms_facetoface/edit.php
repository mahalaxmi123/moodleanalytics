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
 * @author Andrew Hancox <andrewdchancox@googlemail.com> on behalf of Synergy Learning
 * @package corplms
 * @subpackage enrol_corplms_facetoface
 */

/**
 * Adds new instance of enrol_corplms_facetoface to specified course
 * or edits current instance.
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/enrol/corplms_facetoface/edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/corplms_facetoface:config', $context);

$PAGE->set_url('/enrol/corplms_facetoface/edit.php', array('courseid'=>$course->id, 'id'=>$instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('corplms_facetoface')) {
    redirect($return);
}

$plugin = enrol_get_plugin('corplms_facetoface');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'corplms_facetoface', 'id'=>$instanceid), '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));

    $instance = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}

// Merge these two settings to one value for the single selection element.
if ($instance->notifyall and $instance->expirynotify) {
    $instance->expirynotify = 2;
}
unset($instance->notifyall);

$mform = new enrol_corplms_facetoface_edit_form(null, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if ($data->expirynotify == 2) {
        $data->expirynotify = 1;
        $data->notifyall = 1;
    } else {
        $data->notifyall = 0;
    }
    if (!$data->expirynotify) {
        // Keep previous/default value of disabled expirythreshold option.
        $data->expirythreshold = $instance->expirythreshold;
    }
    if (!isset($data->customint6)) {
        // Add previous value of newenrols if disabled.
        $data->customint6 = $instance->customint6;
    }

    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->customint2     = $data->customint2;
        $instance->customint3     = $data->customint3;
        $instance->customint4     = $data->customint4;
        $instance->customint5     = $data->customint5;
        $instance->customint6     = $data->customint6;
        $instance->customint7     = $data->customint7;
        $instance->customint8     = $data->customint8;
        $instance->customtext1    = $data->customtext1;
        $instance->roleid         = $data->roleid;
        $instance->enrolperiod    = $data->enrolperiod;
        $instance->expirynotify   = $data->expirynotify;
        $instance->notifyall      = $data->notifyall;
        $instance->expirythreshold = $data->expirythreshold;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate   = $data->enrolenddate;
        $instance->timemodified   = time();
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->mark_dirty();
        }

    } else {
        $fields = array(
            'status'          => $data->status,
            'name'            => $data->name,
            'customint2'      => $data->customint2,
            'customint3'      => $data->customint3,
            'customint4'      => $data->customint4,
            'customint5'      => $data->customint5,
            'customint6'      => $data->customint6,
            'customint7'      => $data->customint7,
            'customint8'      => $data->customint8,
            'customtext1'     => $data->customtext1,
            'roleid'          => $data->roleid,
            'enrolperiod'     => $data->enrolperiod,
            'expirynotify'    => $data->expirynotify,
            'notifyall'       => $data->notifyall,
            'expirythreshold' => $data->expirythreshold,
            'enrolstartdate'  => $data->enrolstartdate,
            'enrolenddate'    => $data->enrolenddate);
        $plugin->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_corplms_facetoface'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_corplms_facetoface'));
$mform->display();
echo $OUTPUT->footer();
