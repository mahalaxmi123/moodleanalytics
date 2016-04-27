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
 * @author Ciaran Irvine <ciaran.irvine@corplmslms.com>
 * @package tool
 * @subpackage tool_corplms_timezonefix
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig && has_capability('moodle/user:update', context_system::instance())) {
    $ADMIN->add('location', new admin_externalpage('tooltimezonefix', get_string('pluginname', 'tool_corplms_timezonefix'), "$CFG->wwwroot/$CFG->admin/tool/corplms_timezonefix/index.php"));
}
