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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/corplms/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/corplms/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/corplms/core/js/lib/setup.php');

// Page title
$pagetitle = 'assigncompetencytemplates';

///
/// Params
///

// Assign to id
$assignto = required_param('assignto', PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// string of params needed in non-js url strings
$urlparams = array('assignto' => $assignto, 'frameworkid' => $frameworkid, 'nojs' => $nojs, 'returnurl' => urlencode($returnurl), 's' => $s);

///
/// Permissions checks
///

// Setup page
admin_externalpage_setup('positionmanage');

// Load currently assigned competency templates
$positions = new position();
if (!$currentlyassigned = $positions->get_assigned_competency_templates($assignto, $frameworkid)) {
    $currentlyassigned = array();
}

///
/// Display page
///

if (!$nojs) {

    // Load dialog content generator
    $dialog = new corplms_dialog_content_hierarchy_multi('competency', $frameworkid);

    // Templates only
    $dialog->templates_only = true;

    // Toggle treeview only display
    $dialog->show_treeview_only = $treeonly;

    // Load competency templates to display
    $dialog->items = $dialog->hierarchy->get_templates();

    // Set disabled items
    $dialog->disabled_items = $currentlyassigned;

    // Set strings
    $dialog->string_nothingtodisplay = 'notemplateinframework';
    $dialog->select_title = 'locatecompetencytemplate';
    $dialog->selected_title = 'selectedcompetencytemplates';

    // Disable framework picker
    $dialog->disable_picker = true;

    // Display
    echo $dialog->generate_markup();

} else {
    // non JS version of page
    // Check permissions
    $sitecontext = context_system::instance();
    require_capability('corplms/hierarchy:updateposition', $sitecontext);


    // Setup hierarchy object
    $hierarchy = new competency();

    // Load framework
    if (!$framework = $hierarchy->get_framework($frameworkid)) {
        print_error('competencyframeworknotfound', 'corplms_hierarchy');
    }

    echo $OUTPUT->header();
    $out = html_writer::tag('h2', get_string('assigncompetencytemplate', 'corplms_hierarchy'));
    $link = html_writer::link($returnurl, get_string('cancelwithoutassigning','corplms_hierarchy'));
    $out .= html_writer::tag('p', $link);

    if (empty($frameworkid) || $frameworkid == 0) {

        echo build_nojs_frameworkpicker(
            $hierarchy,
            '/corplms/hierarchy/prefix/position/assigncompetencytemplate/find.php',
            array(
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => 1,
                'assignto' => $assignto,
                'frameworkid' => $frameworkid,
            )
        );

    } else {
        $out .= html_writer::start_tag('div', array('id' => 'nojsinstructions'));
        $out .= html_writer::tag('p', get_string('clicktoassigntemplate', 'corplms_hierarchy').' ');
        $out .= html_writer::start_tag('div', array('id' => 'nojsselect'));
        $out .= build_nojs_treeview(
            $items,
            get_string('nounassignedcompetencytemplates', 'corplms_hierarchy'),
            '/corplms/hierarchy/prefix/position/assigncompetencytemplate/assign.php',
            array(
                's' => $s,
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'assignto' => $assignto,
            ),
            '/corplms/hierarchy/prefix/position/assigncompetencytemplate/find.php',
            $urlparams,
            $hierarchy->get_all_parents()
        );
        $out .= html_writer::end_tag('div');
    }
    echo $out;
    echo $OUTPUT->footer();
}
