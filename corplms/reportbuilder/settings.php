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
 * @subpackage reportbuilder
 */

/**
 * Add reportbuilder administration menu settings
 */
require_once($CFG->dirroot . '/corplms/reportbuilder/lib.php');
global $REPORT_BUILDER_EXPORT_OPTIONS;

$ADMIN->add('reports', new admin_category('corplms_reportbuilder', get_string('reportbuilder','corplms_reportbuilder')), 'comments');

// Main report builder settings.
$rb = new admin_settingpage('rbsettings',
                            new lang_string('globalsettings','corplms_reportbuilder'),
                            array('corplms/reportbuilder:managereports'));

foreach ($REPORT_BUILDER_EXPORT_OPTIONS as $option => $code) {
    $formatbyname[$code] = new lang_string('export' . $option, 'corplms_reportbuilder');
    $defaultformats[$code] = 1;
}

$rb->add(new admin_setting_configmulticheckbox('reportbuilder/exportoptions', new lang_string('exportoptions', 'corplms_reportbuilder'),
         new lang_string('reportbuilderexportoptions_help', 'corplms_reportbuilder'), $defaultformats, $formatbyname));

$rb->add(new admin_setting_configcheckbox('reportbuilder/exporttofilesystem', new lang_string('exporttofilesystem', 'corplms_reportbuilder'),
         new lang_string('reportbuilderexporttofilesystem_help', 'corplms_reportbuilder'), false));

$rb->add(new admin_setting_configdirectory('reportbuilder/exporttofilesystempath', new lang_string('exportfilesystempath', 'corplms_reportbuilder'),
         new lang_string('exportfilesystempath_help', 'corplms_reportbuilder'), ''));

$rb->add(new admin_setting_configdaymonthpicker('reportbuilder/financialyear', new lang_string('financialyear', 'corplms_reportbuilder'),
         new lang_string('reportbuilderfinancialyear_help', 'corplms_reportbuilder'), array('d'=> 1, 'm'=>7)));

// Add all above settings to the report builder settings node.
$ADMIN->add('corplms_reportbuilder', $rb);

// Add links to report builder reports.
$ADMIN->add('corplms_reportbuilder', new admin_externalpage('rbmanagereports', new lang_string('managereports','corplms_reportbuilder'),
            new moodle_url('/corplms/reportbuilder/index.php'), array('corplms/reportbuilder:managereports')));
