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
 * Prints an instance of trackedevent.
 *
 * @package     trackedevent
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);

// Module instance id.
$c = optional_param('c', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('trackedevent', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('trackedevent', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($c) {
    $moduleinstance = $DB->get_record('trackedevent', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('trackedevent', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', trackedevent));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_trackedevent\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('trackedevent', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/trackedevent/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
if (time() < $moduleinstance->checkinstart ) {
    echo get_string('eventnotstarted', 'mod_trackedevent');
    exit();
} else if (time() > $moduleinstance->checkinstop) {
    echo get_string('eventalreadyended', 'mod_trackedevent');
    exit();
}
if (has_capability('mod/trackedevent:genqr', $modulecontext)) {
    echo '<a href="genqr.php?id='.$cm->id.'">'.get_string('begingeneratingqr', 'mod_trackedevent').'</a>';
} else {
    $record = $DB->get_record('trackedevent_checkin', ['eventid' => $cm->instance, 'userid' => $USER->id]);
    if (!$record) {
        echo get_string('notcheckedinstatus', 'mod_trackedevent');
    } else {

        // Print this if the user is casually visiting the site.
        echo get_string('checkedinstatus', 'mod_trackedevent');
        echo "<br>";

        // Convert epoch time to human readable time.
        $time = $record->checkintime;
        $usertime = userdate($time);

        echo get_string('checkintimestatus', 'mod_trackedevent').$usertime;
    }
}

echo "<br>";
echo "<br>";
echo "<br>";
echo "<br>";
echo html_writer::link(new moodle_url('/mod/trackedevent/view_data.php?id='.$cm->id), 
    get_string('viewdatahyperlinktext', 'mod_trackedevent'));

echo $OUTPUT->footer();
