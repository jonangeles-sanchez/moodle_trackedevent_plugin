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
 * Plugin administration pages are defined here.
 *
 * @package     trackedevent
 * @category    admin
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


// Only users who have full access to plugin settings can access this.
if ($ADMIN->fulltree) { 

    $options = [
        -1  => get_string('notimeout', 'mod_trackedevent'),
        60  => 60,
        120 => 120,
        300 => 130,
        600 => 600
    ];
   
   // The configuration setting for QR expiration.
   $settings->add(new admin_setting_configselect('mod_trackedevent/qrexpiration',
      get_string('qrlabel', 'mod_trackedevent'),
      get_string('qrexpirationdesc', 'mod_trackedevent'),
      60, // Default.
      $options
   ));
}
