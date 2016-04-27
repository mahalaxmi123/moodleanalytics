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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package corplms
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/corplms/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

///
/// Setup / loading data
///

// Plan id
$planid = required_param('planid', PARAM_INT);
$objectiveid = required_param('objectiveid', PARAM_INT);

// Updated course lists
$idlist = optional_param('update', null, PARAM_SEQUENCE);
if ($idlist == null) {
    $idlist = array();
}
else {
    $idlist = explode(',', $idlist);
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('corplms/plan:accessplan', $context);
$plan = new development_plan($planid);
$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;
$component = $plan->get_component('objective');

if (!$component->can_update_items()) {
    print_error('error:cannotupdateobjectives', 'corplms_plan');
}
if ($plancompleted) {
    print_error('plancompleted', 'corplms_plan');
}

$component->update_linked_components($objectiveid, 'course', $idlist);
if ($linkedcourses =
    $component->get_linked_components($objectiveid, 'course')) {
    echo $plan->get_component('course')->display_linked_courses($linkedcourses);
} else {
    $coursename = get_string('courseplural', 'corplms_plan');
    echo html_writer::tag('p', get_string('nolinkedx', 'corplms_plan', strtolower($coursename)), array('class' => "noitems-assigncourse"));
}
