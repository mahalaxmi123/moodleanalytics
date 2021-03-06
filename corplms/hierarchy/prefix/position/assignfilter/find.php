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
 * @subpackage hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/corplms/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/corplms/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/corplms/core/js/lib/setup.php');

// Page title
$pagetitle = 'assignpositions';

///
/// Params
///

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

///
/// Permissions checks
///

require_login();
$PAGE->set_context(context_system::instance());

///
/// Display page
///

// Load dialog content generator
$dialog = new corplms_dialog_content_hierarchy_multi('position', $frameworkid);

// Toggle treeview only display
$dialog->show_treeview_only = $treeonly;

// Load items to display
$dialog->load_items($parentid);

// Set title
$dialog->selected_title = 'itemstoadd';
$dialog->select_title = '';

// Display
echo $dialog->generate_markup();
