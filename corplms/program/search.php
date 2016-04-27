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
 * @author Yuliya Bozhko <yuliya.bozhko@corplmslms.com>
 * @package corplms
 * @subpackage program
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');

$search    = optional_param('search', '', PARAM_RAW);  // search words
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', '', PARAM_RAW); // how many per page, may be integer or 'all'
$viewtype  = optional_param('viewtype', 'program', PARAM_TEXT);

// List of minimum capabilities which user need to have for editing/moving program.
$capabilities = array('corplms/program:createprogram', 'moodle/category:manage');

// Populate usercatlist with list of category id's with program:createprogram and category:manage capabilities.
$usercatlist = coursecat::make_categories_list($capabilities);

$search = trim(strip_tags($search));

$site = get_site();

$searchcriteria = array();
if (!empty($search)) {
    $searchcriteria['search'] = $search;
}

$urlparams = array();
if ($perpage !== 'all' && !($perpage = (int)$perpage)) {
    $perpage = $CFG->coursesperpage;
} else {
    $urlparams['perpage'] = $perpage;
}
if (!empty($page)) {
    $urlparams['page'] = $page;
}
$urlparams['viewtype'] = $viewtype;

$PAGE->set_url('/corplms/program/search.php', $searchcriteria + $urlparams);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$programrenderer = $PAGE->get_renderer('corplms_program');

if ($CFG->forcelogin) {
    require_login();
}

if ($viewtype == 'program') {
    $strprograms = new lang_string('programs', 'corplms_program');
    $strprogram = new lang_string('program', 'corplms_program');
} else {
    $strprograms = new lang_string('certifications', 'corplms_certification');
    $strprogram = new lang_string('certification', 'corplms_certification');
}
$strsearch = new lang_string('search');
$strsearchresults = new lang_string('searchresults');

$PAGE->navbar->add($strprograms, new moodle_url('/corplms/program/index.php'));
$PAGE->navbar->add($strsearch, new moodle_url('/corplms/program/search.php'));
if (!empty($search)) {
    $PAGE->navbar->add(s($search));
}

if (empty($searchcriteria)) {
    $PAGE->set_title("$site->fullname : $strprogram $strsearch");
} else {
    $PAGE->set_title("$site->fullname : $strprogram $strsearchresults");
    // Link to manage search results should be visible if user have system or category level capability.
    if ((can_edit_in_category() || !empty($usercatlist))) {
        $aurl = new moodle_url('/corplms/program/manage.php', $searchcriteria);
        $managestring = ($viewtype == 'program') ? get_string('manageprograms', 'admin') : get_string('managecertifications', 'corplms_core');
        $searchform = $OUTPUT->single_button($aurl, $managestring, 'get');
    } else {
        $searchform = $programrenderer->program_search_form($viewtype, $search, 'navbar');
    }
    $PAGE->set_button($searchform);
}

$PAGE->set_heading($site->fullname);

echo $OUTPUT->header();
echo $programrenderer->search_programs($searchcriteria, $viewtype);
echo $OUTPUT->footer();
