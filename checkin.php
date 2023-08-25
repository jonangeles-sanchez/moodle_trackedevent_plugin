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
 * Check in a user who scanned a QR code
 *
 * @package     trackedevent
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(__DIR__.'/../../config.php');

// Trackedevent id.
$eventid = required_param('eventid', PARAM_INT);
$qrid = required_param('qrid', PARAM_INT);

$cm = get_coursemodule_from_id('trackedevent', $eventid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('trackedevent', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$msg = '';
$msgtype = \core\output\notification::NOTIFY_INFO;

// Get the record out of the trackedevent_checkin table that matches $qrid.
// If there is no record, invalid URL - print error. We didn't generate that QR.
$record = $DB->get_record('trackedevent_checkin', ['qrid' => $qrid, 'eventid' => $cm->instance]);
if (!$record) {
    $msg = get_string('qrerrornotgenerated', 'mod_trackedevent');
    $msgtype = \core\output\notification::NOTIFY_ERROR;
} else if ($DB->record_exists('trackedevent_checkin', ['userid' => $USER->id, 'eventid' => $cm->instance])) {
    // This user already checked-in - cannot check-in twice.
    $msg = get_string('qrerroralreadycheckedin', 'mod_trackedevent');
    $msgtype = \core\output\notification::NOTIFY_ERROR;
} else {
    // A QR must exist at this point, but has it expired?
    $timecreated = $record->timecreated;
    $timescanned = time();
    $timewindow = get_config('mod_trackedevent', 'qrexpiration');
    $expirationtimestamp = $timecreated + $timewindow;

    if ($timewindow && $timewindow > 0 && ($timescanned > $expirationtimestamp)) {
        $msg = "QR code has expired";
        $msgtype = \core\output\notification::NOTIFY_ERROR;
        $DB->delete_records('trackedevent_checkin', ['qrid' => $qrid, 'eventid' => $cm->instance]);
    } else if ($record->userid != 0) {
        // If there is a record, check the userid field - if 0, ok. If not? Already used!! Error.
        $msg = get_string('qrerroralreadyredeemed', 'mod_trackedevent', $qrid);
        $msgtype = \core\output\notification::NOTIFY_ERROR;
    } else {
        // If everything is ok, set userid to $USER->id and call $DB->update_record().
        $record->userid = $USER->id;
        $record->checkintime = time();
        $DB->update_record('trackedevent_checkin', $record);
        $msg = get_string('qrerrorsuccess', 'mod_trackedevent');
    }
}

// Redirect to event's Moodle page.
$url = new moodle_url('/mod/trackedevent/view.php', ['id' => $eventid]);
redirect($url, $msg, 10, $msgtype);
