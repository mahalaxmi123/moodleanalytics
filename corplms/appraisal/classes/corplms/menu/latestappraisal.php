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
 * Corplms navigation edit page.
 *
 * @package    corplms
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@corplmslms.com>
 */

namespace corplms_appraisal\corplms\menu;

use \corplms_core\corplms\menu\menu as menu;

class latestappraisal extends \corplms_core\corplms\menu\item {

    protected function get_default_title() {
        return get_string('latestappraisal', 'corplms_appraisal');
    }

    protected function get_default_url() {
        return '/corplms/appraisal/myappraisal.php?latest=1';
    }

    public function get_default_sortorder() {
        return 31000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function check_visibility() {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/corplms/appraisal/lib.php');
        if (!corplms_feature_visible('appraisals')) {
            return menu::HIDE_ALWAYS;
        }
        if (\appraisal::can_view_own_appraisals($USER->id)) {
            return menu::SHOW_ALWAYS;
        } else {
            return menu::HIDE_ALWAYS;
        }
    }

    protected function get_default_parent() {
        if (corplms_feature_visible('appraisals')) {
            return '\corplms_appraisal\corplms\menu\appraisal';
        } else {
            return 'root';
        }
    }
}
