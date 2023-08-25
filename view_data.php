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
 * Instantiates form and prints database query.
 *
 * @package     trackedevent
 * 
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
use mod_trackedevent\form\event_query_form;
global $OUTPUT, $DB, $USER;

// $id is used for obtaining user permissions for our moodleform class.
$id = optional_param('id', 0, PARAM_INT);

require_login();

if ($id) {
    $cm = get_coursemodule_from_id('trackedevent', $id, 0, false, MUST_EXIST);
} else {
    print_error(get_string('missingid', 'mod_trackedevent'));
}

$modulecontext = context_module::instance($cm->id);
$pagecontext = context_system::instance();

$title = get_string('viewdatatitle', 'mod_trackedevent');
$url = new moodle_url("/mod/trackedevent/viewdata.php", array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context($pagecontext);

echo $OUTPUT->header();

// Instantiate the myform form from within the plugin.
$mform = new event_query_form(null, ['modcontext' => $modulecontext, 'userid' => $USER->id]);
 
// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    // If there is a cancel element on the form, and it was pressed,
    // then the `is_cancelled()` function will return true.
    // You can handle the cancel operation here.
    // We don't have to worry about this.

} else if ($fromform = $mform->get_data()) {
    // When the form is submitted, and the data is successfully validated,
    // the `get_data()` function will return the data posted in the form.


    $content = 'event,event_end,event_start,student_last_first,checkin_time,student_id,student_email'."\n";


    // Grab the options chosen by the user.
    $event_param = $mform->trackedevent_names[$fromform->eventnames[0]];
    $user_param = $mform->users_last_first[$fromform->userfirstlast[0]];

    // Query data to output to user.
    $sql = 'SELECT ci.*, ev.*, usr.firstname, usr.lastname, usr.id, usr.email 
              FROM {trackedevent_checkin} ci
              JOIN {trackedevent} ev ON ci.eventid = ev.id
              JOIN {user} usr ON ci.userid = usr.id';
    
    $defaultOption = get_string('alloption','mod_trackedevent');

    // If both choices are not default.
    if ($event_param != $defaultOption &&  $user_param != $defaultOption){
        $user_last_first = explode(" ", $user_param);
        $sql .= ' WHERE ev.name = "'.$event_param.'" AND usr.lastname = "'.$user_last_first[0].'" AND usr.firstname = "'.$user_last_first[1].'"';

    // If only the event name is not default.
    } else if ($event_param != $defaultOption) {
        $sql .= ' WHERE ev.name = "'.$event_param.'"';

    // If only the user name is not default.
    } else if ($user_param != $defaultOption) { 
        $user_last_first = explode(" ", $user_param);
        $sql .= ' WHERE usr.lastname = "'.$user_last_first[0].'" AND usr.firstname = "'.$user_last_first[1].'"';
    
    }

    $results = $DB->get_records_sql($sql);
    
    if(!$results){
        echo get_string('notfounderror', 'mod_trackedevent');
        echo "<br>";

    } else {
        echo '<table style="width: 60%">
            <tr>
            <th>'.get_string('event', 'mod_trackedevent').'</th>
            <th>'.get_string('eventstart', 'mod_trackedevent').'</th>
            <th>'.get_string('eventend', 'mod_trackedevent').'</th>
            <th>'.get_string('studentlastandfirst', 'mod_trackedevent').'</th>
            <th>'.get_string('checkintime', 'mod_trackedevent').'</th>
            <th>'.get_string('studentid', 'mod_trackedevent').'</th>
            <th>'.get_string('studentemail', 'mod_trackedevent').'</th>
            </tr>';

        foreach($results as $entry) {
            $event = $entry->name;
            $event_end = $entry->checkinstop;
            $event_start = $entry->checkinstart;
            $student_last_first = $entry->lastname.' '.$entry->firstname; 
            $checkin_time = $entry->checkintime;
            $student_id = $entry->id;
            $student_email = $entry->email;

            echo '<tr>
                <td>'.$event.'</td>
                <td>'.userdate($event_start, get_string('datetimeformat', 'mod_trackedevent')).'</td>
                <td>'.userdate($event_end, get_string('datetimeformat', 'mod_trackedevent')).'</td>
                <td>'.$student_last_first.'</td>
                <td>'.userdate($checkin_time, get_string('datetimeformat', 'mod_trackedevent')).'</td>
                <td>'.$student_id.'</td>
                <td>'.$student_email.'</td>
            </tr>';
        }

        echo '</table>';
        echo "<br>";
        echo html_writer::link(new moodle_url('/mod/trackedevent/download.php', 
            ['event' => $event_param, 'user' => $user_param]), 
            get_string('clicktodownload', 'mod_trackedevent'));

    }


} else {
    // This branch is executed if the form is submitted but the data doesn't
    // validate and the form should be redisplayed or on the first display of the form.

    // Set anydefault data (if any).
    $mform->set_data($toform);

    // Display the form.
    $mform->display();

}

 echo $OUTPUT->footer();

 ?>