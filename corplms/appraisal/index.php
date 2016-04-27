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
 * @author Ciaran Irvine <ciaran.irvine@corplmslms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @package corplms
 * @subpackage corplms_appraisal
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/corplms/appraisal/lib.php');
require_once($CFG->dirroot . '/corplms/appraisal/appraisal_forms.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

require_login();

$role = optional_param('role', null, PARAM_INT);
$subjectid = optional_param('subjectid', $USER->id, PARAM_INT);

// Get the roles that this user has for all the appraisals that it is invloved in.
if ($subjectid == $USER->id) {
    // All the roles that this user plays, for all appraisals they are involved in.
    $rolesplayed = array_keys($DB->get_records('appraisal_role_assignment', array('userid' => $USER->id), 'appraisalrole',
            'DISTINCT appraisalrole'));
} else {
    // Only the roles that this user plays related to the viewed user.
    $sql = "SELECT DISTINCT ara.appraisalrole
              FROM {appraisal_role_assignment} ara
              JOIN {appraisal_user_assignment} aua
                ON ara.appraisaluserassignmentid = aua.id
             WHERE ara.userid = ?
               AND aua.userid = ?
             ORDER BY ara.appraisalrole";
    $rolesplayed = array_keys($DB->get_records_sql($sql, array($USER->id, $subjectid)));
}

// If no role specified then default to the first available.
if (empty($role)) {
    $role = reset($rolesplayed);
}

// Set up the role tabs.
$viewsubjectparams = array('subjectid' => empty($subjectid) ? '' : $subjectid);
$row = array();
$allroles = appraisal::get_roles();
foreach ($rolesplayed as $roleplayed) {
    $urlparams = array_merge(array('role' => $roleplayed), $viewsubjectparams);
    $row[] = new tabobject($roleplayed,
            new moodle_url('/corplms/appraisal/index.php', $urlparams),
            get_string('as' . $allroles[$roleplayed], 'corplms_appraisal'));
}
$tabs[] = $row;

// Set page context.
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

// Set up the highlighted menu (My Team or My Appraisal) and base breadcrumb.
$PAGE->set_corplms_menu_selected('appraisals');
if ($role == appraisal::ROLE_LEARNER) {
    $pageurl = new moodle_url('/corplms/appraisal/index.php');
    $PAGE->navbar->add(get_string('myappraisals', 'corplms_appraisal'), $pageurl);
} else {
    $params = array('role' => $role);
    if (!empty($subjectid)) {
        $params['subjectid'] = $subjectid;
    }
    $pageurl = new moodle_url('/corplms/appraisal/index.php', $params);
    $PAGE->navbar->add(get_string('myteamappraisals', 'corplms_appraisal'), $pageurl);
}

// Start page output.
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('noblocks');
$heading = get_string('myappraisals', 'corplms_appraisal');
$renderer = $PAGE->get_renderer('corplms_appraisal');
$PAGE->set_title($heading);
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

if ($subjectid == $USER->id) {
    echo $OUTPUT->heading(get_string('allappraisals', 'corplms_appraisal'));
} else {
    $user = $DB->get_record('user', array('id' => $subjectid), '*', MUST_EXIST);
    $a = fullname($user);
    echo $OUTPUT->heading(get_string('allappraisalsfor', 'corplms_appraisal', $a));
}

if (count($rolesplayed) > 1) {
    print_tabs($tabs, $role);
}

$viewappraisals = appraisal::get_user_appraisals_extended($subjectid, $role);

echo $renderer->display_user_appraisals($viewappraisals, $role);

// End page output.
echo $OUTPUT->footer();
