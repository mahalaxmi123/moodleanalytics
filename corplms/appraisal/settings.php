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
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @package corplms
 * @subpackage corplms_appraisal
 */

global $SITE, $CFG;

$appraisalcaps = array(
    'corplms/appraisal:manageappraisals',
    'corplms/appraisal:cloneappraisal',
    'corplms/appraisal:assignappraisaltogroup',
    'corplms/appraisal:managenotifications',
    'corplms/appraisal:manageactivation',
    'corplms/appraisal:managepageelements'
);

$feedbackcaps = array(
    'corplms/feedback360:managefeedback360',
    'corplms/feedback360:clonefeedback360',
    'corplms/feedback360:assignfeedback360togroup',
    'corplms/feedback360:manageactivation',
    'corplms/feedback360:managepageelements'
);

if ($hassiteconfig || has_any_capability($appraisalcaps, $systemcontext) || has_any_capability($feedbackcaps, $systemcontext)) {

    $ADMIN->add('appraisals',
        new admin_externalpage('manageappraisals',
            new lang_string('manageappraisals', 'corplms_appraisal'),
            new moodle_url('/corplms/appraisal/manage.php'),
            $appraisalcaps,
            corplms_feature_disabled('appraisals')
        )
    );

    $ADMIN->add('appraisals',
        new admin_externalpage('managefeedback360',
            new lang_string('managefeedback360', 'corplms_feedback360'),
            new moodle_url('/corplms/feedback360/manage.php'),
            $feedbackcaps,
            corplms_feature_disabled('feedback360')
        )
    );

    $ADMIN->add('appraisals',
        new admin_externalpage('reportappraisals',
            new lang_string('reportappraisals', 'corplms_appraisal'),
            new moodle_url('/corplms/appraisal/reports.php'),
            $appraisalcaps,
            corplms_feature_disabled('appraisals')
        )
    );
}
