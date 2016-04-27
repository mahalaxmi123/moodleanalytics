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
 * @author Aaron Barnes <aaron.barnes@corplmslms.com>
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package corplms
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/corplms/plan/lib.php');
require_once($CFG->dirroot.'/corplms/program/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

// Check if programs are enabled.
check_program_enabled();

require_login();

///
/// Setup / loading data
///

// Plan id
$id = required_param('id', PARAM_INT);

// Updated course lists
$idlist = optional_param('update', null, PARAM_SEQUENCE);
if ($idlist == null) {
    $idlist = array();
}
else {
    $idlist = explode(',', $idlist);
}

$plan = new development_plan($id);
$componentname = 'program';
$component = $plan->get_component($componentname);


///
/// Permissions check
///
require_capability('corplms/plan:accessplan', context_system::instance());

if (!$component->can_update_items()) {
    print_error('error:cannotupdateitems', 'corplms_plan');
}

///
/// Update component
///
$component->update_assigned_items($idlist);

echo $component->display_list();
echo $plan->display_plan_message_box();
