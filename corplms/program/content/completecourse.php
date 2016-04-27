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
 * @package    corplms
 * @subpackage program
 * @author     Russell England <russell.england@catalyst-eu.net>
 */

/**
 * @todo : make this a dialog
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');
require_once($CFG->dirroot . '/corplms/program/content/completecourse_form.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$programid = required_param('progid', PARAM_INT);

require_login();

if (!$program = new program($programid)) {
    print_error('error:programid', 'corplms_program');
}

// Check if programs or certifications are enabled.
if ($program->certifid) {
    check_certification_enabled();
} else {
    check_program_enabled();
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

// Permissions.
$usercontext = context_user::instance($userid);
if ((!corplms_is_manager($userid)
    || !has_capability('corplms/program:markstaffcoursecomplete', $usercontext)) && !is_siteadmin()) {
    // If this doesn't then we have show a permissions error.
    print_error('error:notmanagerornopermissions', 'corplms_program');
}

$coursecontext = context_course::instance($course->id);

$params = array();
$params['userid'] = $userid;
$params['courseid'] = $courseid;
$params['progid'] = $programid;

$heading = get_string('completecourse', 'corplms_program');
$PAGE->set_context($coursecontext);
$PAGE->set_heading(format_string($heading));
$PAGE->set_title(format_string($heading));
$PAGE->set_url('/corplms/program/content/completecourse.php', $params);
prog_add_required_learning_base_navlinks($userid);
if (!$progname = $DB->get_field('prog', 'fullname', array('id' => $programid))) {
    print_error('invalidprogid');
}
$progurl = new moodle_url('/corplms/program/required.php', array('userid' => $userid, 'id' => $programid));

$PAGE->navbar->add(format_string($progname), $progurl);
$PAGE->navbar->add($heading);

$completion = new completion_completion(array('userid' => $userid, 'course' => $courseid));
if ($completion->is_complete()) {
    // Toggle as incomplete
    $completion->delete();
    corplms_set_notification(get_string('incompletecourse', 'corplms_program'), $progurl, array('class' => 'notifysuccess'));
}

$mform = new completecourse_form();

if ($mform->is_cancelled()) {
    redirect($progurl);
} else if ($data = $mform->get_data()) {
    // Save and return to prog
    $completion->rpl = $data->rpl;
    $completion->rplgrade = $data->rplgrade;
    $completion->mark_complete($data->timecompleted);
    if (!empty($data->rpl)) {
        $message = get_string('completedcourserpl', 'corplms_program');
    } else {
        $message = get_string('completedcoursemanual', 'corplms_program');
    }
    corplms_set_notification($message, $progurl, array('class' => 'notifysuccess'));
} else {
    $data = new stdClass();
    $data->courseid = $courseid;
    $data->userid = $userid;
    $data->progid = $programid;
    $mform->set_data($data);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$mform->display();

echo $OUTPUT->footer();
