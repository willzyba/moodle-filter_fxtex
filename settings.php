<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for filter_fxtex.
 *
 * @package     filter_fxtex
 * @copyright   2026 CodeCogs, Zyba Ltd. <https://www.codecogs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('filter_fxtex/licence',
        get_string('licence', 'filter_fxtex'),
        get_string('licence_desc', 'filter_fxtex'), '', PARAM_RAW_TRIMMED, 40));

    $settings->add(new admin_setting_configcheckbox('filter_fxtex/singledollar',
        get_string('singledollar', 'filter_fxtex'),
        get_string('singledollar_desc', 'filter_fxtex'), 0));

    $settings->add(new admin_setting_configcheckbox('filter_fxtex/texcompat',
        get_string('texcompat', 'filter_fxtex'),
        get_string('texcompat_desc', 'filter_fxtex'), 0));

    $settings->add(new admin_setting_configtext('filter_fxtex/server',
        get_string('server', 'filter_fxtex'),
        get_string('server_desc', 'filter_fxtex'), \filter_fxtex\text_filter::DEFAULT_SERVER, PARAM_URL, 40));
}
