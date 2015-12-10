<?php

// This file is part of the Mumag plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
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
 * @package    local_saml_siteenrol
 * @copyright  2015, Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2015121000;
$plugin->requires = 2013111800; // Moodle 2.6.0 or newer
$plugin->component = 'local_saml_siteenrol';
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1';

$plugin->dependencies = array(  
    'local_saml_site' => 2015121000
);
