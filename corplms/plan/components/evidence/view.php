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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Russell England <russell.england@corplmslms.com>
 * @package corplms
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/corplms/plan/lib.php');
require_once($CFG->dirroot . '/corplms/core/js/lib/setup.php');
require_once('evidence.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$id = required_param('id', PARAM_INT); // evidence_relation id

$evidence = $DB->get_record('dp_plan_evidence_relation', array('id' => $id), '*', MUST_EXIST);

$plan = new development_plan($evidence->planid);

// Permissions check
$systemcontext = context_system::instance();
if (!has_capability('corplms/plan:accessanyplan', $systemcontext) && ($plan->get_setting('view') < DP_PERMISSION_ALLOW)) {
    print_error('error:nopermissions', 'corplms_plan');
}

$PAGE->set_context($systemcontext);
$PAGE->set_url('/corplms/plan/components/evidence/view.php', array('id' => $id));
$PAGE->set_pagelayout('noblocks');
$PAGE->set_corplms_menu_selected('learningplans');

dp_get_plan_base_navlinks($plan->userid);
$PAGE->navbar->add($plan->name, new moodle_url('/corplms/plan/view.php', array('id' => $plan->id)));
$PAGE->navbar->add(get_string('viewitem', 'corplms_plan'));

$plan->print_header($evidence->component, null, false);

$url = new moodle_url("/corplms/plan/components/{$evidence->component}/view.php",
        array('id' => $evidence->planid, 'itemid' => $evidence->itemid));
$link = $OUTPUT->action_link($url,
    get_string('backtoitem', 'corplms_plan', get_string($evidence->component, 'corplms_plan')));
echo html_writer::tag('p', $link);

echo dp_evidence_relation::display_linked_evidence_detail($id);
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
