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
 * Generates a QR Code for scanning to check in
 *
 * @package     trackedevent
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(__DIR__.'/../../config.php');


// The Trackedevent id.
$id = required_param('id', PARAM_INT);

$cm             = get_coursemodule_from_id('trackedevent', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('trackedevent', array('id' => $cm->instance), '*', MUST_EXIST);


require_login($course, true, $cm); // Makes users enter credientials to get them authenticated.

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/trackedevent/genqr.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// We first ensure our event is still occuring.
// If it's not, let's redirect the user to view.php where 'this event has ended' will display.
if (time() > $moduleinstance->checkinstop) {
    $url = new moodle_url('/mod/trackedevent/view.php', ['id' => $cm->id]);
    redirect($url);
}

echo $OUTPUT->header();
$checkin = new stdClass();
$checkin->eventid = $cm->instance;
$checkin->proctorid = $USER->id; // Currrent logged in user.
$checkin->timecreated = time();
$checkin->checkintime = 0;


$qrid = rand(0, 100000);
while($DB->record_exists('trackedevent_checkin', ['eventid' => $cm->instance, 'qrid' => $qrid])) {
    $qrid = rand(0, 100000);
}
$checkin->qrid = $qrid;
$DB->insert_record('trackedevent_checkin', $checkin);

echo '<h3>'.get_string('eventinfo', 'mod_trackedevent', $moduleinstance->name).'</h3>';
echo "<br>";
echo html_writer::img('qr.php?qrid='.$qrid.'&eventid='.$cm->id, "Check-In QR Code");
echo "<br>";
echo '<a href="genqr.php?id='.$cm->id.'">'.get_string('generatenewqr', 'mod_trackedevent').'</a>';

echo $OUTPUT->footer();


?>
