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
 * @subpackage completionimport
 * @author     Russell England <russell.england@catalyst-eu.net>
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/corplms/completionimport/reset_form.php');
require_once($CFG->dirroot . '/corplms/completionimport/lib.php');
require_once($CFG->libdir . '/adminlib.php');

$pageparams = array();
$pageparams['confirm'] = optional_param('confirm', false, PARAM_BOOL);
$pageparams['course'] = optional_param('course', false, PARAM_BOOL);
$pageparams['certification'] = optional_param('certification', false, PARAM_BOOL);

require_login();

$context = context_system::instance();
require_capability('corplms/completionimport:import', $context);
$heading = get_string('resetimport', 'corplms_completionimport');
$thisurl = '/corplms/completionimport/reset.php';

$PAGE->set_context($context);
$PAGE->set_heading(format_string($heading));
$PAGE->set_title(format_string($heading));
$PAGE->set_url($thisurl, $pageparams);
admin_externalpage_setup('corplms_completionimport_reset');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$mform = new reset_form();

if ($pageparams['confirm']) {
    if (confirm_sesskey()) {
        if ($pageparams['course']) {
            reset_import('course');
        }
        if ($pageparams['certification'] && corplms_feature_visible('certifications')) {
            reset_import('certification');
        }
    }
} else if ($data = $mform->get_data()) {
    $pageparams['confirm'] = true;
    $pageparams['course'] = !empty($data->course);
    $pageparams['certification'] = !empty($data->certification);
    $confirmurl = new moodle_url($thisurl, $pageparams);
    $toreset = '';
    if ($pageparams['course']) {
        $toreset .= 'course';
    }
    if ($pageparams['certification']) {
        if (!empty($toreset)) {
            $toreset .= ', ';
        }
        $toreset .= 'certification';
    }
    echo $OUTPUT->confirm(get_string('resetconfirm', 'corplms_completionimport', $toreset), $confirmurl, $thisurl);
} else {
    $mform->display();
}
echo $OUTPUT->footer();