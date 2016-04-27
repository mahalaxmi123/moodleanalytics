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
 * @author  Simon Coggins <simon.coggins@corplmslms.com>
 * @package corplms
 * @subpackage plan
 */

/**
 * Add learning plans administration menu settings
 */
defined('MOODLE_INTERNAL') || die;

    $ADMIN->add('corplms_plan',
        new admin_externalpage('managetemplates',
            new lang_string('managetemplates', 'corplms_plan'),
            "$CFG->wwwroot/corplms/plan/template/index.php",
            array('corplms/plan:configureplans'),
            corplms_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('corplms_plan',
        new admin_externalpage('priorityscales',
            new lang_string('priorityscales', 'corplms_plan'),
            "$CFG->wwwroot/corplms/plan/priorityscales/index.php",
            array('corplms/plan:configureplans'),
            corplms_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('corplms_plan',
        new admin_externalpage('objectivescales',
            new lang_string('objectivescales', 'corplms_plan'),
            "$CFG->wwwroot/corplms/plan/objectivescales/index.php",
            array('corplms/plan:configureplans'),
            corplms_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('corplms_plan',
        new admin_externalpage('evidencetypes',
            new lang_string('evidencetypes', 'corplms_plan'),
            "$CFG->wwwroot/corplms/plan/evidencetypes/index.php",
            array('corplms/plan:configureplans'),
            corplms_feature_disabled('learningplans')
        )
    );

