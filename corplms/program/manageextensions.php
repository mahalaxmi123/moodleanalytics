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
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @package corplms
 * @subpackage program
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');

require_login();

$userid = optional_param('userid', 0, PARAM_INT);
$extensions = optional_param_array('extension', array(), PARAM_INT);
$reasons = optional_param_array('reasondecision', array(), PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url("/corplms/program/manageextensions.php", array('userid' => $userid)));

if ((!empty($userid) && !corplms_is_manager($userid, $USER->id)) && !is_siteadmin()) {
    print_error('nopermissions', 'error', '', get_string('manageextensions', 'corplms_program'));
}
$extensionsselceted = array_filter($extensions);
if (data_submitted() && confirm_sesskey() && (!empty($extensionsselceted))) {
    $result = prog_process_extensions($extensionsselceted, $reasons);
    if ($result) {
        $total = $result['total'];
        $failcount = $result['failcount'];
        $update_fail_count = $result['updatefailcount'];
        $update_extension_count = $total;
        if ($total == 0) {
            redirect('manageextensions.php');
        } elseif ($update_fail_count == $update_extension_count && $update_fail_count > 0) {
            corplms_set_notification(get_string('updateextensionfailall', 'corplms_program'), 'manageextensions.php?userid='.$userid);
        } elseif ($update_fail_count > 0) {
            corplms_set_notification(get_string('updateextensionfailcount', 'corplms_program', $update_fail_count), 'manageextensions.php?userid='.$userid);
        } else {
            corplms_set_notification(get_string('updateextensionsuccess', 'corplms_program'), 'manageextensions.php?userid='.$userid, array('class' => 'notifysuccess'));
        }
    }
}

$heading = get_string('manageextensions', 'corplms_program');
$pagetitle = get_string('extensions', 'corplms_program');

$PAGE->navbar->add($heading);
$PAGE->set_title($pagetitle);
$PAGE->set_heading('');
echo $OUTPUT->header();

if (!empty($userid)) {
    $backstr = "&laquo" . get_string('backtoallextrequests', 'corplms_program');
    $url = new moodle_url('/corplms/program/manageextensions.php');
    $link = html_writer::link($url, $backstr);
    echo html_writer::start_tag('p') . $link . html_writer::end_tag('p');
}

echo $OUTPUT->heading($heading);

if (!empty($userid)) {
    if (!$user = $DB->get_record('user', array('id' => $userid))) {
        print_error(get_string('error:invaliduser', 'corplms_program'));
    }
    $user_fullname = fullname($user);

    $staff_ids = $userid;
} elseif ($staff_members = corplms_get_staff()) {
    $staff_ids = $staff_members;
}

if (!empty($staff_ids)) {
    list($staff_sql, $staff_params) = $DB->get_in_or_equal($staff_ids);
    $sql = "SELECT * FROM {prog_extension}
        WHERE status = 0
        AND userid {$staff_sql}";

    $extensions = $DB->get_records_sql($sql, $staff_params);

    if ($extensions) {

        $columns[] = 'user';
        $headers[] = get_string('name');
        $columns[] = 'program';
        $headers[] = get_string('program', 'corplms_program');
        $columns[] = 'currentdate';
        $headers[] = get_string('currentduedate', 'corplms_program');
        $columns[] = 'extensiondate';
        $headers[] = get_string('extensiondate', 'corplms_program');
        $columns[] = 'reason';
        $headers[] = get_string('reason', 'corplms_program');
        $columns[] = 'reasonfordecision';
        $headers[] = get_string('reasonfordecision', 'corplms_message');
        $columns[] = 'grant';
        $headers[] = get_string('grantdeny', 'corplms_program');

        $table = new flexible_table('Extensions');
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->define_baseurl(new moodle_url("/corplms/program/manageextensions.php"));
        $table->set_attribute('class', 'fullwidth');
        $table->setup();

        $options = array(
            PROG_EXTENSION_GRANT => get_string('grant', 'corplms_program'),
            PROG_EXTENSION_DENY => get_string('deny', 'corplms_program'),
        );

        $currenturl = qualified_me();
        echo html_writer::start_tag('form', array('id'=>'program-extension-update', 'action'=>$currenturl, 'method'=>'POST'));

        foreach ($extensions as $extension) {
            $tablerow = array();

            if ($prog_completion = $DB->get_record('prog_completion', array('programid' => $extension->programid, 'userid' => $extension->userid, 'coursesetid' => 0))) {
                $duedatestr = empty($prog_completion->timedue) ? get_string('duedatenotset', 'corplms_program') : userdate($prog_completion->timedue, get_string('strftimedate', 'langconfig'), $CFG->timezone, false);
            }

            $prog_name = $DB->get_field('prog', 'fullname', array('id' => $extension->programid));
            $prog_name = empty($prog_name) ? '' : $prog_name;

            $user = $DB->get_record('user', array('id' => $extension->userid));
            $tablerow[] = fullname($user);
            $tablerow[] = $prog_name;
            $tablerow[] = $duedatestr;
            $tablerow[] = userdate($extension->extensiondate, get_string('strftimedate', 'langconfig'), $CFG->timezone, false);
            $tablerow[] = $extension->extensionreason;

            $pulldown_name = "extension[{$extension->id}]";
            $attributes = array();
            $attributes['disabled'] = false;
            $attributes['tabindex'] = 0;
            $attributes['class'] = 'approval';
            $attributes['id'] = null;

            $tablerow[] = html_writer::empty_tag('input', array('name' =>"reasondecision[{$extension->id}]", 'type' =>'text'));

            $pulldown_menu = html_writer::select($options, $pulldown_name, $extension->status, array(0 => 'choose'), $attributes);
            $tablerow[] = $pulldown_menu;
            $table->add_data($tablerow);
        }

        if (!empty($userid)) {
            echo html_writer::tag('p', get_string('viewinguserextrequests', 'corplms_program', $user_fullname));
        }

        echo html_writer::empty_tag('input', array('type'=>'hidden', 'id' => 'sesskey', 'name'=>'sesskey', 'value'=>sesskey()));
        $table->finish_html();
        echo html_writer::empty_tag('br');
        echo html_writer::empty_tag('input', array('type'=>'submit', 'name' => 'submitbutton', 'value' => get_string('updateextensions', 'corplms_program')));
        html_writer::end_tag('form');

    } elseif (!empty($userid)) {
        echo html_writer::tag('p', get_string('nouserextensions', 'corplms_program', $user_fullname));
    } else {
        echo html_writer::tag('p', get_string('noextensions', 'corplms_program'));
    }
} else {
    echo html_writer::tag('p', get_string('notmanager', 'corplms_program'));
}

echo $OUTPUT->footer();
