<?php

// This file is part of Moodle Schoolloop Sync
// Author: Keith D Swenson
// Copyright 2011, All rights reserved.

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_sl.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';


class sl_roster {

    var $parsedFile;
    var $DB;

    public function __construct($nDB,$baseURL,$teacherid,$username,$password) {
        $this->DB = $nDB;
        $ch = curl_init();
        $fileURL = $baseURL.'api/roster?teacher_id='.urlencode($teacherid);
        curl_setopt($ch, CURLOPT_URL, $fileURL);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERPWD,"$username:$password");
        $xml = curl_exec($ch);
        $firstFive = substr($xml, 0, 5);
        if ($firstFive != "<?xml") {
            print_error("For ".htmlspecialchars($fileURL)." Got this result: ".htmlspecialchars($xml));
        }
        $this->parsedFile = new SimpleXMLElement($xml);
    }

    public function getRoster() {
        return $this->parsedFile->roster;
    }

    public function getCourses() {
        return $this->parsedFile->roster->teachers->teacher->courses->course;
    }

    public function getSectionNames()
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {

            $courseName = $ccourse['name'];
            foreach($ccourse->periods->period as $section) {
                $retval["".$section['systemID']] = "Section ".$section['number']." (of) ".$courseName;
            }
        }
        return $retval;
    }

    public function findSection($secId)
    {
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            foreach($ccourse->periods->period as $section) {
                if ($secId == $section['systemID']) {
                    return $section;
                }
            }
        }
    }


    public function getAssignmentsOfSection($secId)
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            foreach($ccourse->periods->period as $section) {
                if ($secId == $section['systemID']) {
                    foreach ($section->assignments->assignment as $assignment) {
                        $retval[] = "".$assignment['systemID'].":".$assignment['title'];
                    }
                }
            }
        }
        return $retval;
    }

    /**
    * return all the assignments from a particular class section
    * using a map to eliminate duplicates and return system id for each assignment
    */
    public function getAssignmentMap($secId)
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            foreach($ccourse->periods->period as $section) {
                if ($secId == $section['systemID']) {
                    foreach ($section->assignments->assignment as $assignment) {
                        $retval["".$assignment['title']] = "".$assignment['systemID'];
                    }
                }
            }
        }
        return $retval;
    }

    /**
    * return all the assignments across all class sections
    * using a map to eliminate duplicates
    */
    public function getAllAssignmentMap()
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            foreach($ccourse->periods->period as $section) {
                foreach ($section->assignments->assignment as $assignment) {
                    $retval["".$assignment['title']] = "".$assignment['title'];
                }
            }
        }
        return $retval;
    }


    public function getStudentsOfPeriod($periodID)
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            $courseName = "".$ccourse['name'];
            foreach($ccourse->periods->period as $period) {
                $thisPeriod = "".$period['systemID'];
                if ($periodID == $thisPeriod) {
                    foreach ($period->students->student as $student) {
                        $studentid = "".$student['permID'];
                        $user = $this->DB->get_record('user', array('idnumber'=>$studentid));
                        $moodleuserid = "".$user->id;
                        $record = array(
                               'studentSID' => "".$student['systemID'],
                               'studentId'  => $studentid,
                               'firstName'  => "".$student['firstName'],
                               'lastName'   => "".$student['lastName'],
                               'moodleId'   => $moodleuserid,
                               'course'     => $courseName,
                               'sectionSID' => "".$student['sectionSystemID'],
                               'period'     => $thisPeriod);
                        $retval[$studentid] = $record;
                    }
                }
            }
        }
        return $retval;
    }

    public function getAllStudentsOnRoster()
    {
        $retval = array();
        foreach ($this->parsedFile->roster->teachers->teacher->courses->course as $ccourse) {
            $courseName = "".$ccourse['name'];
            foreach($ccourse->periods->period as $section) {
                $thisSysid = "".$section['systemID'];
                $thisPeriod = "".$section['number'];
                foreach ($section->students->student as $assignment) {
                    $studentid = "".$assignment['permID'];
                    $user = $this->DB->get_record('user', array('idnumber'=>$studentid));
                    $moodleuserid = "".$user->id;
                    $moodleFirstName = "".$user->firstname;
                    $moodleLastName = "".$user->lastname;
                    $record = array('systemID' => "".$assignment['systemID'],
                           'studentId' => $studentid,
                           'firstName' => "".$assignment['firstName'],
                           'lastName'  => "".$assignment['lastName'],
                           'moodleId'  => $moodleuserid,
                           'course'    => $courseName,
                           'period'    => $thisPeriod,
                           'moodleName'=> "$moodleFirstName $moodleLastName");

                    $specialKey = "{$assignment['lastName']} {$assignment['firstName']}";
                    $retval[$specialKey] = $record;
                }
            }
        }
        ksort($retval);
        return $retval;
    }



    function getSchoolLoopGrades($course, $periodId)
    {
        $studentList = $this->getStudentsOfPeriod($periodId);
        $amap = $this->getAssignmentMap($periodId);
        $gMods = $this->lookUpCourseGradeItems($course, $amap);
        $res3 = array();
        foreach ($studentList as $key => $student)
        {
            $studentSystemId = $student['studentSID'];
            $grade4student = $this->lookUpGradesForStudent($student['moodleId'], $gMods, $amap);
            foreach ($grade4student as $grade)
            {
                if ($grade['assignmentSID']!="") {
                    $val = array( 'sectionSID' => $student['sectionSID'],
                                  'studentSID' => $studentSystemId,
                                  'assignmentSID' => $grade['assignmentSID'],
                                  'grade'     => $grade['finalgrade'],
                                  'itemname'  => $grade['itemname'],
                                  'firstName' => $student['firstName'],
                                  'lastName'  => $student['lastName']);
                    $res3[] = $val;
                }
            }
        }
        return $res3;
    }

    function getSchoolLoopGradesForAssignment($course, $assignment)
    {
        $sections = $this->getSectionNames();
        $res3 = array();
        foreach ($sections as $periodId => $description)
        {
            $studentList = $this->getStudentsOfPeriod($periodId);
            $amap = $this->getAssignmentMap($periodId);
            $gMods = $this->lookUpCourseGradeItems($course, $amap);
            foreach ($studentList as $key => $student)
            {
                $studentSystemId = $student['studentSID'];
                $grade4student = $this->lookUpGradesForStudent($student['moodleId'], $gMods, $amap);
                foreach ($grade4student as $grade)
                {
                    if ($grade['assignmentSID']!="" && $grade['itemname']==$assignment) {
                        $val = array( 'sectionSID' => $student['sectionSID'],
                                      'sectionName' => $description,
                                      'studentSID' => $studentSystemId,
                                      'assignmentSID' => $grade['assignmentSID'],
                                      'grade'     => $grade['finalgrade'],
                                      'itemname'  => $grade['itemname'],
                                      'firstName' => $student['firstName'],
                                      'lastName'  => $student['lastName']);
                        $res3[] = $val;
                    }
                }
            }
        }
        return $res3;
    }


    function lookUpGradesForStudent($studentId, $gradeItemMap, $assignmentIdMap) {
        $grade4student = $this->DB->get_records('grade_grades', array('userid'=>$studentId));
        $retval = array();
        foreach($grade4student as $grade) {
            $itemid      = "".$grade->itemid;
            $iteminfo = $gradeItemMap[$itemid];
            $assignname = $iteminfo['name'];
            $val = array('itemid'      => $itemid,
                         'userid'      => "".$grade->userid,
                         'finalgrade'  => "".$grade->finalgrade,
                         'rawgrademax' => "".$grade->rawgrademax,
                         'rawgrademin' => "".$grade->rawgrademin,
                         'instance'    => "".$iteminfo['instance'],
                         'module'      => "".$iteminfo['module'],
                         'itemname'    => $assignname,
                         'assignmentSID' => $assignmentIdMap[$assignname]);
            $retval[] = $val;
        }
        return $retval;
    }


    function lookUpCourseModules($courseid, $assignmentIdMap) {
        $items = $this->DB->get_records('course_modules', array('course'=>$courseid));
        $retval = array();
        foreach($items as $item) {
            $modid = "".$item->module;
            $itemid = "".$item->id;
            $idnumber = "".$item->idnumber;
            $instance = "".$item->instance;
            if ($modid==1) {
                $module = 'assignment';
            }
            else if ($modid==13) {
                $module = 'quiz';
            }
            else {
                continue;
            }
            $modrecord = $this->DB->get_record($module, array('id'=>$instance));
            $aname = "".$modrecord->name;
            $sysid = $assignmentIdMap[$aname];
            $val = array('id' => $itemid,
                         'instance' => $instance,
                         'module'   => $module,
                         'name'     => $aname,
                         'systemID' => $sysid);
            $retval[$instance] = $val;
        }
        return $retval;
    }

    function getCourseModulesMap($courseid) {
        $items = $this->DB->get_records('course_modules', array('course'=>$courseid));
        $retval = array();
        foreach($items as $item) {
            $modid = "".$item->module;
            $itemid = "".$item->id;
            $idnumber = "".$item->idnumber;
            $instance = "".$item->instance;
            if ($modid==1) {
                $module = 'assignment';
            }
            else if ($modid==13) {
                $module = 'quiz';
            }
            else {
                continue;
            }
            $modrecord = $this->DB->get_record($module, array('id'=>$instance));
            $aname = "".$modrecord->name;
            $val = array('id' => $itemid,
                         'instance' => $instance,
                         'module'   => $module,
                         'name'     => $aname);
            $retval[$aname] = $val;
        }
        return $retval;
    }



    function lookUpCourseGradeItems($courseid, $assignmentIdMap) {
        $gitems = $this->DB->get_records('grade_items', array('courseid'=>$courseid));
        $retval = array();
        foreach($gitems as $gitem) {
            $itemid     = "".$gitem->id;
            $itemname   = "".$gitem->itemname;
            $itemmodule = "".$gitem->itemmodule;
            $iteminstance = "".$gitem->iteminstance;
            $sysid = $assignmentIdMap[$itemname];
            $val = array('id' => $itemid,
                         'instance' => $iteminstance,
                         'module'   => $itemmodule,
                         'name'     => $itemname,
                         'systemID' => $sysid);
            $retval[$itemid] = $val;
        }
        return $retval;
    }


    function getCourseGradeItemMap($courseid) {
        $gitems = $this->DB->get_records('grade_items', array('courseid'=>$courseid));
        $retval = array();
        foreach($gitems as $gitem) {
            $itemname   = "".$gitem->itemname;
            if (strlen($itemname)==0) {
                continue;
            }
            $itemid     = "".$gitem->id;
            $itemmodule = "".$gitem->itemmodule;
            $iteminstance = "".$gitem->iteminstance;
            $val = array('id' => $itemid,
                         'instance' => $iteminstance,
                         'module'   => $itemmodule,
                         'name'     => $itemname);
            $retval[$itemname] = $val;
        }
        return $retval;
    }


}







function print_tablex($title, $table, $cols)
{
    if (!isset($cols)) {
        $cols = get_columns($table);
    }
    $alt = new alternator("FFFFFF", "EEEEEE");
    $needsExtra = (count($cols) % 2);
    echo ("<h3>$title</h3>\n");
    echo ("<table cellpadding=\"5\">\n");
    echo ("<tr><td bgcolor=\"{$alt->getValue()}\"></td>");
    foreach ($cols as $col) {
        echo "<td bgcolor=\"{$alt->getValue()}\">$col</td>";
    }
    echo ("</tr>\n");

    foreach ($table as $idx => $row)
    {
        if ($needsExtra==1) {
            $alt->getValue();
        }
        echo "<tr><td bgcolor=\"{$alt->getValue()}\">$idx</td>\n";
        foreach ($cols as $col) {
            $val = $row->$col;
            echo "<td bgcolor=\"{$alt->getValue()}\">$val</td>\n";
        }
        echo "</tr>\n";
    }
    echo ("</table>\n");
}
function print_tabley($title, $table, $cols)
{
    if (!isset($cols)) {
        $cols = get_columns($table);
    }
    $alt = new alternator("FFFFFF", "EEEEEE");
    $needsExtra = (count($cols) % 2);
    echo ("<h3>$title</h3>\n");
    echo ("<table cellpadding=\"5\">\n");
    echo ("<tr><td bgcolor=\"{$alt->getValue()}\"></td>");
    foreach ($cols as $col) {
        echo "<td bgcolor=\"{$alt->getValue()}\">$col</td>";
    }
    echo ("</tr>\n");

    foreach ($table as $idx => $row)
    {
        if ($needsExtra==1) {
            $alt->getValue();
        }
        echo "<tr><td bgcolor=\"{$alt->getValue()}\">$idx</td>\n";
        foreach ($cols as $col) {
            $val = $row[$col];
            echo "<td bgcolor=\"{$alt->getValue()}\">$val</td>\n";
        }
        echo "</tr>\n";
    }
    echo ("</table>\n");
}
function print_tablez($title, $table)
{
    $alt = new alternator("FFFFFF", "EEEEEE");
    echo ("<h3>$title</h3>\n");
    echo ("<table cellpadding=\"5\">\n");

    echo ("<tr><td bgcolor=\"{$alt->getValue()}\">index</td><td bgcolor=\"{$alt->getValue()}\">value</td></tr>\n");
    foreach ($table as $idx => $row)
    {
        $alt->getValue();  //extra one needed so each row alternates
        echo "<tr><td bgcolor=\"{$alt->getValue()}\">$idx</td>\n";
        echo "<td bgcolor=\"{$alt->getValue()}\">$row</td>\n";
        echo "</tr>\n";
    }
    echo ("</table>\n");
}
function get_columns($myArray)
{
    $rescols = array();
    foreach ($myArray as $row) {
        foreach ($row as $key => $val) {
            if (in_array("".$key, $rescols)==false) {
                $rescols[] = "".$key;
            }
        }
    }
    return $rescols;
}
echo "</pre>\n</body></html>";


class alternator {
    var $val1;
    var $val2;

    public function __construct($v1, $v2) {
        $this->val1 = $v1;
        $this->val2 = $v2;
    }

    public function getValue() {
        $x = $this->val1;
        $this->val1 = $this->val2;
        $this->val2 = $x;
        return $x;
    }
}


?>
