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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once '../../../config.php';

require_once $CFG->libdir.'/formslib.php';
require_once $CFG->dirroot.'/grade/export/grade_export_form.php';

class grade_export_form_sl extends grade_export_form {
    function definition() {
        grade_export_form::definition();

        $mform =& $this->_form;

        $mform->addElement('header', 'add_sl', "Things for School Loop");

        $mform->addElement('advcheckbox', 'export_feedback2', 'Hoopdy doo');
    }
}

