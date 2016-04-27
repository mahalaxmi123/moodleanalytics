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

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/corplms/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/corplms/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/corplms/core/js/lib/setup.php');
require_once($CFG->dirroot.'/corplms/hierarchy/prefix/competency/related/lib.php');


///
/// Setup / loading data
///

// Competency id
$compid = required_param('id', PARAM_INT);

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// should we show hidden frameworks?
$showhidden = optional_param('showhidden', false, PARAM_BOOL);

// check they have permissions on hidden frameworks in case parameter is changed manually
$context = context_system::instance();
if ($showhidden && !has_capability('corplms/hierarchy:updatecompetencyframeworks', $context)) {
    print_error('nopermviewhiddenframeworks', 'corplms_hierarchy');
}

// show search tab instead of browse
$search = optional_param('search', false, PARAM_BOOL);

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// string of params needed in non-js url strings
$urlparams = array('id' => $compid, 'frameworkid' => $frameworkid, 'nojs' => $nojs, 'returnurl' => urlencode($returnurl), 's' => $s);

// Setup page
admin_externalpage_setup('competencymanage', '', array(), '/corplms/hierarchy/prefix/competency/related/add.php');

$alreadyselected = array();
if ($alreadyrelated = comp_relation_get_relations($compid)) {
    list($alreadyrelated_sql, $alreadyrelated_params) = $DB->get_in_or_equal($alreadyrelated);
    $alreadyselected = $DB->get_records_select('comp', 'id ' . $alreadyrelated_sql, $alreadyrelated_params, '', 'id, fullname');
}
$alreadyrelated[$compid] = $compid; // competency can't be related to itself

///
/// Display page
///


if (!$nojs) {
    // Load dialog content generator
    $dialog = new corplms_dialog_content_hierarchy_multi('competency', $frameworkid, $showhidden);

    // Toggle treeview only display
    $dialog->show_treeview_only = $treeonly;

    // Load items to display
    $dialog->load_items($parentid);

    // Make a note of the current item (competency) id
    $dialog->customdata['current_item_id'] = $compid;

    // Set disabled items
    $dialog->disabled_items = $alreadyrelated;

    // Set title
    $dialog->selected_title = 'itemstoadd';
    $dialog->selected_items = $alreadyselected;
    // Addition url parameters
    $dialog->urlparams = array('id' => $compid);
    // Display
    echo $dialog->generate_markup();

} else {
    // non JS version of page
    // Check permissions
    $sitecontext = context_system::instance();
    require_capability('corplms/hierarchy:updatecompetency', $sitecontext);

    // Setup hierarchy object
    $hierarchy = new competency();

    // Load framework
    if (!$framework = $hierarchy->get_framework($frameworkid, $showhidden)) {
        print_error('competencyframeworknotfound', 'corplms_hierarchy');
    }

    // Load competencies to display
    $competencies = $hierarchy->get_items_by_parent($parentid);

    echo $OUTPUT->header();
    $out = html_writer::tag('h2', get_string('assignrelatedcompetencies', 'corplms_hierarchy'));
    $link = html_writer::link($returnurl, get_string('cancelwithoutassigning','corplms_hierarchy'));
    $out .= html_writer::tag('p', $link);

    if (empty($frameworkid) || $frameworkid == 0) {

        $out .= build_nojs_frameworkpicker(
            $hierarchy,
            '/corplms/hierarchy/prefix/competency/related/find.php',
            array(
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => 1,
                'id' => $compid,
            )
        );

    } else {
        $out .= html_writer::start_tag('div', array('id' => 'nojsinstructions'));
        $out .= build_nojs_breadcrumbs($hierarchy,
            $parentid,
            '/corplms/hierarchy/prefix/competency/related/find.php',
            array(
                'id' => $compid,
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => $nojs,
                'frameworkid' => $frameworkid,
            )
        );
        $out .= html_writer::tag('p', get_string('clicktoassign', 'corplms_hierarchy') . ' ' . get_string('clicktoviewchildren', 'corplms_hierarchy'));
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'nojsselect'));
        $out .=build_nojs_treeview(
            $competencies,
            get_string('nochildcompetenciesfound', 'corplms_hierarchy'),
            '/corplms/hierarchy/prefix/competency/related/save.php',
            array(
                's' => $s,
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'id' => $compid,
            ),
            '/corplms/hierarchy/prefix/competency/related/find.php',
            $urlparams,
            $hierarchy->get_all_parents(),
            $alreadyrelated
        );
        $out .= html_writer::end_tag('div');
    }
    echo $out;
    echo $OUTPUT->footer();
}
