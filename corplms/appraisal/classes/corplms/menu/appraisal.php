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

class appraisal extends \corplms_core\corplms\menu\item {

    protected function get_default_title() {
        return get_string('appraisal', 'corplms_appraisal');
    }

    protected function get_default_url() {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/corplms/appraisal/lib.php');

        $isappraisalenabled = corplms_feature_visible('appraisals');
        $viewownappraisals = $isappraisalenabled && \appraisal::can_view_own_appraisals($USER->id);
        $viewappraisals = $isappraisalenabled && ($viewownappraisals || \appraisal::can_view_staff_appraisals($USER->id));

        $feedbackmenu = new \corplms_feedback360\corplms\menu\feedback360(array());
        $viewfeedback = $feedbackmenu->get_visibility();

        $goalmenu = new \corplms_hierarchy\corplms\menu\mygoals(array());
        $viewgoals = $goalmenu->get_visibility();

        if ($viewownappraisals) {
            return '/corplms/appraisal/myappraisal.php?latest=1';
        } else if ($viewappraisals) {
            return '/corplms/appraisal/index.php';
        } else if ($viewfeedback) {
            return '/corplms/feedback360/index.php';
        } else if ($viewgoals) {
            return '/corplms/hierarchy/prefix/goal/mygoals.php';
        }
    }

    public function get_default_sortorder() {
        return 30000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function check_visibility() {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/corplms/appraisal/lib.php');

        $isappraisalenabled = corplms_feature_visible('appraisals');
        $viewownappraisals = $isappraisalenabled && \appraisal::can_view_own_appraisals($USER->id);
        $viewappraisals = $isappraisalenabled && ($viewownappraisals || \appraisal::can_view_staff_appraisals($USER->id));

        $feedbackmenu = new \corplms_feedback360\corplms\menu\feedback360(array());
        $viewfeedback = $feedbackmenu->get_visibility();

        $goalmenu = new \corplms_hierarchy\corplms\menu\mygoals(array());
        $viewgoals = $goalmenu->get_visibility();

        if ($viewappraisals || $viewfeedback || $viewgoals) {
            return menu::SHOW_ALWAYS;
        } else {
            return menu::HIDE_ALWAYS;
        }
    }
}
