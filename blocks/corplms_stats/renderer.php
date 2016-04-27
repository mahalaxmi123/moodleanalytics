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
 * @subpackage blocks_corplms_stats
 */
class block_corplms_stats_renderer extends plugin_renderer_base {

    /**
     * The display the statistics block.
     *
     * @param array $stats the list of stats to display in the block.
     *
     * @returns the rendered results.
     */
    public function display_stats_list($stats) {
        if (count($stats) == 0) {
            return '';
        }

        $output = get_string('statdesc', 'block_corplms_stats');
        $items = array();
        foreach ($stats as $stat) {
            $items[] = $this->display_stats_list_item($stat);
        }
        $output .= html_writer::tag('ul', implode($items));
        return $output;
    }

    /**
     * Displays a single statistic.
     *
     * @param object $stat An object containing the image and string stating the statistic.
     *
     * @returns the rendered statistic.
     */
    public function display_stats_list_item($stat) {
        $statstr = $stat->icon;
        $statstr .= html_writer::tag('p', $stat->displaystring);
        $output = html_writer::tag('li', $statstr);
        return $output;
    }
}
