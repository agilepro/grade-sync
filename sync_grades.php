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
if (!isset($id) || !isset($url) || !isset($teacherid) || !isset($username) || !isset($password))
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

$pickArray = array();
$pickcount = 0;
while ($pickcount<500) {
    $pickname = "pick$pickcount";
    $picktest = optional_param($pickname, '~', PARAM_TEXT);
    if ($picktest != '~') {
        $pickArray[] = $picktest;
    }
    $pickcount++;
}

$readFile = new sl_roster($DB,$url,$teacherid,$username,$password);
echo "<html><body><pre>";

$assignmentList = '';
foreach($pickArray as $assignment) {
    $res1 = $readFile->getSchoolLoopGradesForAssignment($id, $assignment);
    foreach($res1 as $singleGrade) {
        $postfile .= "{$singleGrade['sectionSID']} {$singleGrade['studentSID']} {$singleGrade['assignmentSID']} {$singleGrade['grade']}\n";
    }
    $assignmentList .= htmlspecialchars($assignment).', ';
}
echo <<<PAGE_HEAD

</pre>
<p></p><ul>
<h3>School Loop Synchronization - Grade</h3>

<p>Below are the students that are listed by Schoolloop</p>

<table>
    <col width="150">
    <col width="300">
    <col width="300">
   <tr valign="top"><td>Moodle Course:</td>
       <td colspan="2">$courseName_xx</td></tr>
   <tr valign="top"><td>Action:</td>
       <td colspan="2">Sync Students</td></tr>
   <tr valign="top"><td>Section:</td>
       <td colspan="2">All Sections</td></tr>
   <tr valign="top"><td>Assignment:</td>
       <td colspan="2">$assignmentList</td></tr>
<form action="pick_assignment.php" method="get">
<input type="hidden" name="action"  value="Sync Grades">
   <tr valign="top"><td></td>
       <td><input type="submit" value="Choose a Different Assignment"></td>
       <td></td></tr>
</form>
<form action="index.php" method="get">
<input type="hidden" name="id" value="$id">
   <tr valign="top"><td></td>
       <td><input type="submit" value="Start Over"></td>
       <td></td></tr>
</form>
</table>


<table cellpadding="3">
<col width="300">
<col width="200">
<col width="20">
<col width="80">
<col width="300">
<tr>
  <td>Section / Assignment</td>
  <td>Student Name</td>
  <td> &nbsp; </td>
  <td>Grade</td>
  <td></td>
</tr>
PAGE_HEAD;

$prevName = "";

foreach($pickArray as $assignment) {
    $res1 = $readFile->getSchoolLoopGradesForAssignment($id, $assignment);
    foreach ( $res1 as $row ) {
        echo "<tr>\n";
        $studentName = $row['firstName'].' '.$row['lastName'];
        $sectionName = $row['sectionName'];
        if ($sectionName == $prevName) {
            echo "<td></td>\n";
        }
        else {
            echo "<td>".htmlspecialchars($sectionName)." / ".htmlspecialchars($assignment)."</td>\n";
            $prevName = $sectionName;
        }
        echo "<td>".htmlspecialchars($studentName)."</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td>".htmlspecialchars($row['grade'])."</td>\n";
        echo "</tr>\n";
    }
}

echo <<<PAGE_FOOT
</table>

<p></p>
<hr/>

<h3>Here is the Grade Report for Schoolloop</h3>
<form action="post_grades.php" method="post">
<textarea name="postfile" rows="10" cols="60">$postfile</textarea><br/>
<input type="submit" value="Post to Schoolloop">
</form>
<br/>
<hr/>
<br/>
PAGE_FOOT;

//this space reserved for debug stuff
//print_tablez("Section List" , $sectionList);
//$assignmentIdMap1 = $readFile->getAssignmentMap($section);
//print_tablez("Assignment Map" , $assignmentIdMap1);
//$studentList = $readFile->getStudentsOfPeriod($section);
//print_tabley("Student List" , $studentList);
//print_tabley("Grade Report for $section" , $res1);
//$gMods = $readFile->lookUpCourseGradeItems($id, $assignmentIdMap1);
//print_tabley("GMods" , $gMods);


echo("</body></html>");





