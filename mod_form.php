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
 * The main trackedevent configuration form.
 *
 * @package     trackedevent
 * @copyright   2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    trackedevent
 * @copyright  2023 martygilbert@gmail.com, jonathanas1337@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_trackedevent_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('trackedeventname', 'trackedevent'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'trackedeventname', 'trackedevent');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of trackedevent settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'checkinstartend', get_string('checkinstartend', 'trackedevent'));
		$mform->setExpanded('checkinstartend');

		$mform->addElement('date_time_selector', 'checkinstart', get_string('checkinstart', 'trackedevent'));
		$mform->addElement('date_time_selector', 'checkinstop', get_string('checkinstop', 'trackedevent'));

        // Add standard grading elements.

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }


	/**
	 * Perform minimal validation on the settings form
	 * @param array $data
	 * @param array $files
	 */
	public function validation($data, $files) {
		$errors = parent::validation($data, $files);


		if (!empty($data['checkinstart']) && !empty($data['checkinstop'])) {
			if ($data['checkinstop'] < ($data['checkinstart'] + (30 * 60))) {
				$errors['checkinstart'] = get_string('invalidminwindow', 'trackedevent');
			}
		} 

		if (!empty($data['checkinstart']) && !empty($data['checkinstop'])) {
			if ($data['checkinstop'] <= $data['checkinstart']) {
				$errors['checkinstart'] = get_string('invalidwindow', 'trackedevent');
			}
		} 

		if (!empty($data['checkinstop'])) {
			if ($data['checkinstop'] < time()) {
				$errors['checkinstop'] = get_string('checkinstopinpast', 'trackedevent');
			}
		}

		return $errors;
	}

}
