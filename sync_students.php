<?php

// This file is part of Moodle Schoolloop Sync
// Author: Keith D Swenson
// Copyright 2011, All rights reserved.

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_sl.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';
require_once 'sl_roster.php';

mb_internal_encoding("UTF-8");
header('Content-type: text/html;charset=UTF-8');

$id        = $_SESSION['sl_course'];
$url       = $_SESSION['sl_url'];
$teacherid = $_SESSION['sl_teacherid'];
$username  = $_SESSION['sl_username'];
$password  = $_SESSION['sl_password'];
if (!isset($url) || !isset($teacherid) || !isset($username) || !isset($password))
{
    Header( "HTTP/1.1 301 Moved Permanently" );
    Header( "Location: index.php?id=".urlencode($id) );
    die();
}

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

$courseName = $course->fullname;
$courseName_xx = htmlspecialchars($courseName);


$readFile = new sl_roster($DB,$url,$teacherid,$username,$password);
$studentList = $readFile->getAllStudentsOnRoster();

$sectionList = $readFile->getSectionNames();

echo <<<PAGE_HEAD
<html><body>

<p></p><ul>
<h3>School Loop Synchronization - Students</h3>

<p>Below are the students that are listed by Schoolloop</p>

<table>
    <col width="150">
    <col width="300">
    <col width="300">
   <tr valign="top"><td>Moodle Course:</td>
       <td colspan="2">$courseName_xx</td></tr>
   <tr valign="top"><td>Action:</td>
       <td colspan="2">Sync Students</td></tr>
<form action="index.php" method="get">
<input type="hidden" name="id" value="$id">
   <tr valign="top"><td></td>
       <td><input type="submit" value="Start Over"></td>
       <td></td></tr>
</form>
</table>

<p>Below you see the listing of all the students from the Schoolloop roster.
   For each student, you can see theie student id, and whether there is an
   associated user record in Moodle.  If there is no associated student
   record, then you may need to either (1) create a new student record, or
   (2) find an existing student record that has an incorrect or missind student id.</p>

<table  cellpadding="3">
<tr>
  <td>Student Name</td>
  <td>Student ID</td>
  <td>Class &amp; Period</td>
  <td> &nbsp; &nbsp; </td>
  <td>Moodle ID</td>
  <td>Name in Moodle</td>
</tr>
PAGE_HEAD;

foreach($studentList as $student)
{
    echo "<tr>\n";
    echo "  <td>$student[firstName] $student[lastName]</td>\n";
    echo "  <td>$student[studentId]</td>\n";
    echo "  <td>$student[course] $student[period]</td>\n";
    echo "  <td> &nbsp; &nbsp; </td>\n";
    if ($student[moodleId]=="") {
        echo "  <td colspan=\"2\"><font color=\"DDDDDD\">No student found in Moodle with id $student[studentId]</font></td>\n";
    }
    else {
        echo "  <td align=\"right\"><a href=\"../../../user/profile.php?id=".urlencode($student[moodleId])."\">$student[moodleId]</a></td>\n";
        echo "  <td>$student[moodleName]</td>\n";
    }
    echo "</tr>\n";
}
echo "</table>\n";

echo <<<PAGE_FOOT

<p></p>
<hr/>

PAGE_FOOT;

//this space reserved for debug stuff

echo("</body></html>");





