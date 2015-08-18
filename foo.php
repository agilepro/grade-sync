<?php

// This file is part of Moodle Schoolloop Sync
// Author: Keith D Swenson
// Copyright 2011, All rights reserved.

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_sl.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';
require_once 'sl_roster.php';

$id  = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);
require_capability('moodle/grade:export', $context);
require_capability('gradeexport/sl:view', $context);

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}



echo "<html><body><p><a href=\"../../report/user/index.php?id=$id\">The Course User Report</a></p><ul>\n";


$fileloc1 = "SampleRoster.xml";
$fileloc2 = "http://bobcat:8080/wu/samples/SampleRoster.xml";
$username = "";
$password = "";

//sample (test) values
$secid = "en442-01";
$secid2 = "en442-02";
$student1 = 21;


$readFile = new sl_roster($DB,$fileloc2,502,$username,$password);




$assignmentIdMap1 = $readFile->getAssignmentMap($secid);
$assignmentIdMap2 = $readFile->getAssignmentMap($secid2);

print_tablez("Sections from Schoolloop", $readFile->getSectionNames());
print_tablez("Assignments for $secid", $assignmentIdMap1);
print_tablez("Assignments for $secid2",$assignmentIdMap2);

$studentList = $readFile->getStudentsOfPeriod($secid);
print_tabley("Section $secid - Students from Schoolloop", $studentList, array('firstName','lastName', 'moodleId', 'studentId', 'systemID'));
$studentList2 = $readFile->getStudentsOfPeriod($secid2);
print_tabley("Section $secid2 - Students from Schoolloop", $studentList2, array('firstName','lastName', 'moodleId', 'studentId', 'systemID'));


$cMods = $readFile->lookUpCourseModules($id, $assignmentIdMap1);
print_tabley("Modules for course $id (system id from $secid)", $cMods, array('id', 'module', 'name', 'systemID'));

$gMods = $readFile->lookUpCourseGradeItems($id, $assignmentIdMap1);
print_tabley("Grade Items course $id (system id from $secid)", $gMods, array('id', 'module', 'name', 'systemID'));


$grade = $DB->get_records('grade_items', array('courseid'=>$id));
print_tablex("Grades Items for Course $id", $grade, array('courseid', 'itemname', 'iteminstance', 'itemmodule'));

echo('<hr/>');
echo('<pre>');
//print_r($grade4student);

echo('</pre>');
$grade4student = $readFile->lookUpGradesForStudent($student1, $gMods, $assignmentIdMap1);
print_tabley("Grades for Student $student1", $grade4student, array('itemid', 'userid',
            'finalgrade', 'module', 'instance', 'name', 'aSystemID'));
echo('<hr/>');


$res1 = $readFile->getScoolLoopGrades($id, $secid);
print_tabley("Final Report for $secid" , $res1, array('sectionId', 'studentId',
            'assignId', 'grade', 'firstName', 'lastName'));
$res1 = $readFile->getScoolLoopGrades($id, $secid2);
print_tabley("Final Report for $secid2" , $res1, array('sectionId', 'studentId',
            'assignId', 'grade', 'firstName', 'lastName'));




function curious($nm, $arrayin) {
    foreach ($arrayin as $key => $val) {
        //if (is_array($val)) {
            curious("$nm -> $key", $val);
        //}
        //else {
            echo "$nm -> $key : \n";
        //}
    }
}



/*

function getScoolLoopGrades($DB, $readFile, $course, $sectionId)
{
    $studentList = $readFile->getStudentsOfPeriod($DB, $sectionId);
    $amap = $readFile->getAssignmentMap($sectionId);
    $gMods = lookUpCourseGradeItems($DB, $course, $amap);
    $res3 = array();
    foreach ($studentList as $key => $student)
    {
        $studentSystemId = $student['systemID'];
        $grade4student = lookUpGradesForStudent($DB, $key, $gMods, $amap);
        foreach ($grade4student as $grade)
        {
            if ($grade['aSystemID']!="") {
                $val = array( 'sectionId' => $sectionId,
                              'studentId' => $student['systemID'],
                              'assignId'  => $grade['aSystemID'],
                              'grade'     => $grade['finalgrade'],
                              'firstName' => $student['firstName'],
                              'lastName'  => $student['lastName']);
                $res3[] = $val;
            }
        }
    }
    return $res3;
}

function lookUpGradesForStudent($DB, $studentId, $gradeItemMap, $assignmentIdMap) {
    $grade4student = $DB->get_records('grade_grades', array('userid'=>$studentId));
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
                     'name'        => $assignname,
                     'aSystemID'   => $assignmentIdMap[$assignname]);
        $retval[] = $val;
    }
    return $retval;
}


function lookUpCourseModules($DB, $courseid, $assignmentIdMap) {
    $items = $DB->get_records('course_modules', array('course'=>$courseid));
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
        $modrecord = $DB->get_record($module, array('id'=>$instance));
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


function lookUpCourseGradeItems($DB, $courseid, $assignmentIdMap) {
    $gitems = $DB->get_records('grade_items', array('courseid'=>$courseid));
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







function print_tablex($title, $table, $cols)
{
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
*/



