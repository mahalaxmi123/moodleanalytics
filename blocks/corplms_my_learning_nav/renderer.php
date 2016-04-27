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
 * @author Brian Barnes <brian.barnes@corplmslms.com>
 * @package corplms
 * @subpackage block_corplms_my_learning_nav
 */
class block_corplms_my_learning_nav_renderer extends plugin_renderer_base {
    /**
     * print out the Corplms My Learning nav section
     * @return html_writer::table
     */
    public function my_learning_nav() {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        $output = html_writer::start_tag('ul');

        $usercontext = context_user::instance($USER->id);
        if (has_capability('corplms/plan:accessplan', $usercontext)) {
            $text = get_string('developmentplan', 'corplms_core');
            $icon = new pix_icon('plan', $text, 'corplms_core');
            $url = new moodle_url('/corplms/plan/index.php');
            $output .= html_writer::start_tag('li');
            $output .= $this->output->action_icon($url, $icon);
            $output .= html_writer::link($url, $text);
            $output .= html_writer::end_tag('li');
        }

        $text = get_string('bookings', 'corplms_core');
        $icon = new pix_icon('bookings', $text, 'corplms_core');
        $url = new moodle_url('/my/bookings.php?userid=' . $USER->id);
        $output .= html_writer::start_tag('li');
        $output .= $this->output->action_icon($url, $icon);
        $output .= html_writer::link($url, $text);
        $output .= html_writer::end_tag('li');

        $text = get_string('recordoflearning', 'corplms_core');
        $icon = new pix_icon('record', $text, 'corplms_core');
        $url = new moodle_url('/corplms/plan/record/index.php?userid='.$USER->id);
        $output .= html_writer::start_tag('li');
        $output .= $this->output->action_icon($url, $icon);
        $output .= html_writer::link($url, $text);
        $output .= html_writer::end_tag('li');

        $output .= html_writer::end_tag('ul');

        return $output;
    }
}
