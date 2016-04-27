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
 * @package corplms
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/corplms/plan/lib.php');
require_once($CFG->dirroot . '/corplms/core/js/lib/setup.php');
require_once($CFG->dirroot . '/corplms/plan/components/evidence/evidence.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

// Check if programs are enabled.
check_program_enabled();

require_login();

$id = required_param('id', PARAM_INT); // plan id
$progassid = required_param('itemid', PARAM_INT); // program assignment id
$action = optional_param('action', 'view', PARAM_TEXT);

$plan = new development_plan($id);
$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;

//Permissions check
$systemcontext = context_system::instance();
if (!has_capability('corplms/plan:accessanyplan', $systemcontext) && ($plan->get_setting('view') < DP_PERMISSION_ALLOW)) {
        print_error('error:nopermissions', 'corplms_plan');
}

// Check the item is in this plan
if (!$DB->record_exists('dp_plan_program_assign', array('planid' => $plan->id, 'id' => $progassid))) {
    print_error('error:itemnotinplan', 'corplms_plan');
}

$PAGE->set_context($systemcontext);
$PAGE->set_url('/corplms/plan/components/program/view.php', array('id' => $id, 'itemid' => $progassid));
$PAGE->set_pagelayout('noblocks');
$PAGE->set_corplms_menu_selected('learningplans');

//Javascript include
local_js(array(
    CORPLMS_JS_DIALOG,
    CORPLMS_JS_TREEVIEW
));

// Get extension dialog content
if ($programid = $DB->get_field('dp_plan_program_assign', 'programid', array('id' => $progassid))) {
    $PAGE->requires->strings_for_js(array('pleaseentervaliddate', 'pleaseentervalidreason', 'extensionrequest', 'cancel', 'ok'), 'corplms_program');
    $notify_html = addslashes_js(trim($OUTPUT->notification(get_string("extensionrequestsent", "corplms_program"), "notifysuccess")));
    $notify_html_fail = addslashes_js(trim($OUTPUT->notification(get_string("extensionrequestnotsent", "corplms_program"), null)));
    $args = array('args'=>'{"id":'.$programid.', "userid":'.$USER->id.', "user_fullname":'.json_encode(fullname($USER)).', "notify_html_fail":"'.$notify_html_fail.'", "notify_html":"'.$notify_html.'"}');
    $jsmodule = array(
                 'name' => 'corplms_programview',
                 'fullpath' => '/corplms/program/view/program_view.js',
                 'requires' => array('json')
              );
    $PAGE->requires->js_init_call('M.corplms_programview.init',$args, false, $jsmodule);
}

$componentname = 'program';
$component = $plan->get_component($componentname);
$canupdate = $component->can_update_items();

$evidence = new dp_evidence_relation($id, $componentname, $progassid);

$currenturl = new moodle_url('/corplms/plan/components/program/view.php', array('id' => $id, 'itemid' => $progassid));

$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan', 'corplms_plan').': '.$fullname);

/// Javascript stuff
// If we are showing dialog
if ($canupdate) {
    $sesskey = sesskey();
    $PAGE->requires->string_for_js('save', 'corplms_core');
    $PAGE->requires->string_for_js('cancel', 'moodle');
    $PAGE->requires->string_for_js('addlinkedevidence', 'corplms_plan');

    // Get evidence picker
    $jsmodule_evidence = array(
        'name' => 'corplms_plan_find_evidence',
        'fullpath' => '/corplms/plan/components/evidence/find-evidence.js',
        'requires' => array('json'));
    $PAGE->requires->js_init_call('M.corplms_plan_find_evidence.init',
            array('args' => '{"plan_id":'.$id.', "component_name":"'.$componentname.'", "item_id":'.$progassid.'}'),
            false, $jsmodule_evidence);
}

// Check if we are performing an action
if ($data = data_submitted() && $canupdate) {
    require_sesskey();

    if ($action === 'removelinkedevidence' && !$plan->is_complete()) {
        $selectedids = optional_param_array('delete_linked_evidence', array(), PARAM_BOOL);
        $evidence->remove_linked_evidence($selectedids, $currenturl);
    }
}

dp_get_plan_base_navlinks($plan->userid);
$PAGE->navbar->add($fullname, new moodle_url('/corplms/plan/view.php', array('id' => $plan->id)));
$PAGE->navbar->add(get_string('viewitem', 'corplms_plan'));


$plan->print_header($componentname);

print $component->display_back_to_index_link();

print $component->display_program_detail($progassid);

// Display linked evidence
echo $evidence->display_linked_evidence($currenturl, $canupdate, $plancompleted);

// Comments
echo $OUTPUT->heading(get_string('comments', 'corplms_plan'), 3, null, 'comments');
require_once($CFG->dirroot.'/comment/lib.php');
comment::init();
$options = new stdClass;
$options->area    = 'plan_program_item';
$options->context = $systemcontext;
$options->itemid  = $progassid;
$options->showcount = true;
$options->component = 'corplms_plan';
$options->autostart = true;
$options->notoggle = true;
$comment = new comment($options);
echo $comment->output(true);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
