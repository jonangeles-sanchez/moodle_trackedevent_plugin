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
 * This will download the event data as a CSV. 
 *
 * @package    trackedevent
 * @copyright  2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$event_param = required_param('event', PARAM_TEXT);
$user_param  = required_param('user', PARAM_TEXT);

global $OUTPUT, $DB, $USER;

$title = 'Download all Data';
$pagetitle = $title;
$url = new moodle_url("/mod/trackedevent/download.php");

$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

require_login();

$filename = date("Y_m_d", time()).'_EventData.csv';
header('Content-type: application/ms-excel');
header('Content-Disposition: attachment; filename='.$filename);

$content = 'event,event_start,event_end,student_last_first,checkin_time,student_id,student_email'."\n";

// Decode the parameters.
$event_param = urldecode($event_param); 
$user_param  = urldecode($user_param);

$sql = 'SELECT ci.*, ev.*, usr.firstname, usr.lastname, usr.id, usr.email 
          FROM {trackedevent_checkin} ci
          JOIN {trackedevent} ev ON ci.eventid = ev.id
          JOIN {user} usr ON ci.userid = usr.id';

$defaultOption = get_string('alloption', 'mod_trackedevent');

// If both choices are not default.
if ($event_param != $defaultOption &&  $user_param != $defaultOption){
    $user_last_first = explode(" ", $user_param);
    $sql .= ' WHERE ev.name = "'.$event_param.'" AND usr.lastname = "'.$user_last_first[0].'" AND usr.firstname = "'.$user_last_first[1].'"';

// If the event name is not default.
} else if ($event_param != $defaultOption) {
    $sql .= ' WHERE ev.name = "'.$event_param.'"';

// If the user name is not default.
} else if ($user_param != $defaultOption) { 
    $user_last_first = explode(" ", $user_param);
    $sql .= ' WHERE usr.lastname = "'.$user_last_first[0].'" AND usr.firstname = "'.$user_last_first[1].'"';

}

$results = $DB->get_records_sql($sql);

foreach($results as $entry) {
    $event = $entry->name;
    $event_end = $entry->checkinstop;
    $event_start = $entry->checkinstart;
    //$student_first = $entry->firstname; 
    $student_last_first = $entry->lastname.' '.$entry->firstname; 
    $checkin_time = $entry->checkintime;
    $student_id = $entry->id;
    $student_email = $entry->email;

     $content .= '"'.$event.'",'.
    '"'.userdate($event_start, get_string('datetimeformat', 'mod_trackedevent')).'",'.
    '"'.userdate($event_end, get_string('datetimeformat', 'mod_trackedevent')).'",'.
    '"'.$student_last_first.'",'.
    '"'.userdate($checkin_time, get_string('datetimeformat', 'mod_trackedevent')).'",'.
    '"'.$student_id.'",'.
    '"'.$student_email."\"\n";
}
echo $content;
exit();