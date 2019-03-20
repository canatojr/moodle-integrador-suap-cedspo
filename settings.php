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
 * SUAP > Moodle block settings.
 *
 * @package   block_suap
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext(
            'block_suap_url_api',
            get_string('urlapi', 'block_suap'),
            get_string('configurlapi', 'block_suap'),
            'https://suap.ifsp.edu.br/edu/api',
            PARAM_URL
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_token',
            get_string('token', 'block_suap'),
            get_string('configtoken', 'block_suap'),
            'ad89f708cdba20d73c05112a2dcadfa489e9d508',
            PARAM_ALPHANUMEXT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_id_campus',
            get_string('idcampus', 'block_suap'),
            get_string('configidcampus', 'block_suap'),
            7,
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_min_year',
            get_string('minyear', 'block_suap'),
            get_string('configminyear', 'block_suap'),
            2017,
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_nivel_curso',
            get_string('nivelcurso', 'block_suap'),
            get_string('confignivelcurso', 'block_suap'),
            2,
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_nivel_turma',
            get_string('nivelturma', 'block_suap'),
            get_string('confignivelturma', 'block_suap'),
            3,
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_suap_nivel_periodo',
            get_string('nivelperiodo', 'block_suap'),
            get_string('confignivelperiodo', 'block_suap'),
            4,
            PARAM_INT
        )
    );
}
