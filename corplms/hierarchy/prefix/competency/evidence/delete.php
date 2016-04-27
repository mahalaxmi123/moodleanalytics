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
 * @author Simon Coggins <simon.coggins@corplmslms.com>
 * @package corplms
 * @subpackage corplms_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

///
/// Setup / loading data
///

// competency id
$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', $CFG->wwwroot, PARAM_LOCALURL);
$confirm = optional_param('confirm', 0, PARAM_INT);
$s = optional_param('s', null, PARAM_TEXT);

// only redirect back if we are sure that's where they came from
if ($s != sesskey()) {
    $returnurl = $CFG->wwwroot;
}

// Check perms
$sitecontext = context_system::instance();
require_capability('corplms/hierarchy:updatecompetency', $sitecontext);

if ($confirm) { // confirmation made
    if (confirm_sesskey()) {
        if ($DB->delete_records('comp_record', array('id' => $id))) {
            redirect($returnurl);
        } else {
            redirect($returnurl,get_string('couldnotdeletece', 'corplms_hierarchy'));
        }
    }
}

$pagetitle = format_string(get_string('deletecompetencyevidence', 'corplms_hierarchy'));

$PAGE->navbar->add(get_string('deletecompetencyevidence', 'corplms_hierarchy'));
$PAGE->set_title($pagetitle);
$PAGE->set_heading('');
echo $OUTPUT->header($pagetitle);

// prompt to delete
echo $OUTPUT->confirm(get_string('confirmdeletece', 'corplms_hierarchy'), qualified_me(), $returnurl);


echo $OUTPUT->footer();
