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
 * @author Russell England <russell.england@corplmslms.com>
 * @package corplms
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/corplms/plan/lib.php');
require_once('dialog_content_linked_evidence.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

// Setup / loading data
$planid = required_param('planid', PARAM_INT);
$componentname = required_param('component', PARAM_ALPHA);
$itemid = required_param('itemid', PARAM_INT);

// Load plan
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('corplms/plan:accessplan', $context);

// Access control check
$plan = new development_plan($planid);
$userid = $plan->userid;
$componentobject = $plan->get_component($componentname);
if (!$permission = $componentobject->can_update_items()) {
    print_error('error:cannotupdateevidence', 'corplms_plan');
}

// Setup dialog

// Load dialog content generator
$dialog = new corplms_dialog_linked_evidence_content_evidence();

// Set type to multiple
$dialog->type = corplms_dialog_content::TYPE_CHOICE_MULTI;
$dialog->selected_title = 'itemstoadd';
$dialog->urlparams = array('planid' => $planid, 'component' => $componentname, 'itemid' => $itemid);
$dialog->customdata['userid'] = $userid;

// Load evidence
$dialog->load_evidence($userid);

// Load selected items
$dialog->load_selected($planid, $componentname, $itemid);

// Display page
echo $dialog->generate_markup();
