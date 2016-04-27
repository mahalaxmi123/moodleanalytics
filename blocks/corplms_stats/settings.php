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
 * @author Dan Marsden <dan@catalyst.net.nz>
 * @package corplms
 * @subpackage blocks_corplms_stats
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/corplms_stats/locallib.php');
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_corplms_stats_minutesbetweensession', get_string('minutesbetweensession', 'block_corplms_stats'),
                       get_string('minutesbetweensessiondesc', 'block_corplms_stats'), 30, PARAM_INT));
    $settings->add(new admin_setting_configtime('block_corplms_stats_sche_hour', 'block_corplms_stats_sche_minute', get_string('executeat'),
                                                 get_string('executeathelp', 'block_corplms_stats'), array('h' => 0, 'm' => 0)));
}
?>