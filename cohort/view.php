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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package corplms
 * @subpackage cohort
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

$id        = required_param('id', PARAM_INT);
$delete    = optional_param('delete', false, PARAM_BOOL);
$confirm   = optional_param('confirm', false, PARAM_BOOL);
$clone     = optional_param('clone', false, PARAM_BOOL);
$cancelurl = optional_param('cancelurl', false, PARAM_LOCALURL);

$url = new moodle_url('/cohort/view.php', array('id' => $id, 'delete' => $delete,
    'confirm' => $confirm, 'clone' => $clone, 'cancelurl' => $cancelurl));
admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout'=>'report'));

$context = context_system::instance();
require_capability('moodle/cohort:view', $context);

$cohort = $DB->get_record('cohort', array('id' => $id));
if (!$cohort) {
    print_error('error:doesnotexist', 'cohort');
}
if ($cohort->cohorttype == cohort::TYPE_DYNAMIC) {
    $cohort->rulesetoperator = $DB->get_field('cohort_rule_collections', 'rulesetoperator', array('id' => $cohort->draftcollectionid));
}
$membercount = $DB->count_records('cohort_members', array('cohortid' => $cohort->id));

$returnurl = new moodle_url('/cohort/index.php');

if (!$cancelurl) {
    $nourl = new moodle_url("$CFG->wwwroot/cohort/view.php", array('id'=>$cohort->id));
} else {
    $nourl = new moodle_url($cancelurl);
}

if ($delete && $cohort->id) {
    if ($confirm and confirm_sesskey()) {
        $result = cohort_delete_cohort($cohort);
        corplms_set_notification(get_string('successfullydeleted', 'corplms_cohort'), $returnurl->out(), array('class' => 'notifysuccess'));
    }

    $yesurl = new moodle_url('/cohort/view.php', array('id'=>$cohort->id, 'delete'=>1, 'confirm'=>1,'sesskey'=>sesskey()));

    $strheading = get_string('delcohort', 'corplms_cohort');
    corplms_cohort_navlinks($cohort->id, $cohort->name, $strheading);
    echo $OUTPUT->header();

    $buttoncontinue = new single_button($yesurl, get_string('yes'), 'post');
    $buttoncancel   = new single_button($nourl, get_string('no'), 'post');
    echo $OUTPUT->confirm(get_string('delconfirm', 'corplms_cohort', format_string($cohort->name)), $buttoncontinue, $buttoncancel);

    echo $OUTPUT->footer();
    die();
}

if ($clone && $cohort->id) {
    if ($confirm && confirm_sesskey()) {
        $result = corplms_cohort_clone_cohort($cohort->id);
        add_to_log(SITEID, 'cohort', 'clone', 'cohort/view.php?id='.$result, "origid={$cohort->id}");
        if ($result) {
            $successurl = new moodle_url($CFG->wwwroot.'/cohort/view.php', array('id'=>$result));
            corplms_set_notification(
                get_string('successfullycloned', 'corplms_cohort'),
                $successurl->out(),
                array('class' => 'notifysuccess')
            );
        } else {
            corplms_set_notification(get_string('failedtoclone', 'corplms_cohort'), $returnurl->out());
        }
    }
    $yesurl = new moodle_url($CFG->wwwroot.'/cohort/view.php', array('id'=>$cohort->id, 'clone'=>1, 'confirm'=>1, 'sesskey'=>sesskey()));

    $strheading = get_string('clonecohort', 'corplms_cohort');
    corplms_cohort_navlinks($cohort->id, $cohort->name, $strheading);
    echo $OUTPUT->header();

    $buttoncontinue = new single_button($yesurl, get_string('yes'), 'post');
    $buttoncancel   = new single_button($nourl, get_string('no'), 'post');
    echo $OUTPUT->confirm(get_string('cloneconfirm', 'corplms_cohort', format_string($cohort->name)), $buttoncontinue, $buttoncancel);
    echo $OUTPUT->footer();
    die();
}

$strheading = get_string('overview', 'corplms_cohort');
corplms_cohort_navlinks($cohort->id, $cohort->name, $strheading);
echo $OUTPUT->header();


echo $OUTPUT->heading(format_string($cohort->name));

echo cohort_print_tabs('view', $cohort->id, $cohort->cohorttype, $cohort);

// Verify if the cohort has a broken rule.
$trace = new null_progress_trace();
$cohortbrokenrules = corplms_cohort_broken_rules(null, $cohort->id, $trace);
if (!empty($cohortbrokenrules)) {
    corplms_display_broken_rules_box();
}

$out = '';
$out .= html_writer::start_tag('div', array('class' => 'mform'));
$out .= html_writer::start_tag('fieldset');

$item = html_writer::tag('div', get_string('type', 'corplms_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', ($cohort->cohorttype == cohort::TYPE_DYNAMIC) ? get_string('dynamic', 'corplms_cohort') : get_string('set', 'corplms_cohort'), array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('idnumber', 'corplms_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $cohort->idnumber, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required ');

$item = html_writer::tag('div', get_string('description'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $cohort->description, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('startdate', 'corplms_cohort'), array('class' => 'fitemtitle'));
$ud = ($cohort->enddate) ? userdate($cohort->startdate, get_string('strftimedate')) : '';
$item .= html_writer::tag('div', $ud, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required ');

$item = html_writer::tag('div', get_string('enddate', 'corplms_cohort'), array('class' => 'fitemtitle'));
$ud = ($cohort->enddate) ? userdate($cohort->enddate, get_string('strftimedate')) : '';
$item .= html_writer::tag('div', $ud, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('alertmembers', 'corplms_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $COHORT_ALERT[$cohort->alertmembers], array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required');


$item = html_writer::tag('div', get_string('members', 'corplms_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $membercount, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$out .= html_writer::end_tag('fieldset') . html_writer::end_tag('div');

if ($cohort->cohorttype == cohort::TYPE_DYNAMIC) {
    require_once($CFG->dirroot.'/corplms/cohort/rules/lib.php');
    $rulesets = $DB->get_records('cohort_rulesets', array('rulecollectionid' => $cohort->activecollectionid), 'sortorder');

    $out .= $OUTPUT->heading(get_string('dynamiccohortcriterialower', 'corplms_cohort'));

    $out .= html_writer::start_tag('div', array('class' => 'mform'));
    $out .= html_writer::start_tag('fieldset');

    $item = html_writer::tag('div', get_string('rulestitle', 'corplms_cohort'), array('class' => 'fitemtitle'));
    if (empty($rulesets)) {
        $item .= html_writer::tag('div', get_string('norules', 'corplms_cohort'), array('class' => 'felement ftext'));
    } else {
        $item .= html_writer::start_tag('div', array('class' => 'felement ftext'));
        $item .= html_writer::start_tag('ul');
        $cohortoperator = get_string($COHORT_RULES_OP[$cohort->rulesetoperator], 'corplms_cohort');
        $i = 0;
        foreach ($rulesets as $ruleset) {
            $item .= html_writer::start_tag('li');
            if ($i > 0) {
                $item .= $cohortoperator . ' ';
            }
            $item .= $ruleset->name;
            $rulesetoperator = get_string($COHORT_RULES_OP[$ruleset->operator], 'corplms_cohort');
            $rules = $DB->get_records('cohort_rules', array('rulesetid' => $ruleset->id));
            $j = 0;
            if (!empty($rules)) { // Print its rules
                $item .= html_writer::start_tag('ul');
                foreach ($rules as $rulerec) {
                    $item .= html_writer::start_tag('li');
                    if ($j) {
                        $item .= $rulesetoperator . ' ';
                    }
                    $rule = cohort_rules_get_rule_definition($rulerec->ruletype, $rulerec->name);
                    if ($rule) {
                        $rule->sqlhandler->fetch($rulerec->id);
                        $rule->ui->setParamValues($rule->sqlhandler->paramvalues);
                        $item .= $rule->ui->getRuleDescription($rulerec->id);
                    } else { // Broken rule.
                        $a = new stdClass();
                        $a->type = $rulerec->ruletype;
                        $a->name = $rulerec->name;
                        $content = get_string('cohortbrokenrule', 'corplms_cohort', $a);
                        $item .= html_writer::tag('b', $content, array('class' => 'error'));
                    }
                    $item .= html_writer::end_tag('li');
                    $j++;
                }
                $item .= html_writer::end_tag('ul');
            }
            $item .= html_writer::end_tag('li');
        }
        $item .= html_writer::end_tag('ul');
        $item .= html_writer::end_tag('div');
    }

    $out .= $OUTPUT->container($item, 'fitem required alternate');


    $out .= html_writer::end_tag('fieldset') . html_writer::end_tag('div');

} // End if cohort type is dynamic.

echo $out;
$cloneurl = new moodle_url("/cohort/view.php", array('id'=>$cohort->id, 'clone'=>1));
$delurl = new moodle_url("/cohort/view.php", array('id'=>$cohort->id, 'delete'=>1));
echo $OUTPUT->single_button($cloneurl, get_string('clonethiscohort', 'corplms_cohort'));
echo $OUTPUT->single_button($delurl, get_string('deletethiscohort', 'corplms_cohort'));
echo $OUTPUT->footer();
