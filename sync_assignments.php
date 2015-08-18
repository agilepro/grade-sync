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

$section  = required_param('section', PARAM_TEXT);   // SL section to synchronize


$readFile = new sl_roster($DB,$url,$teacherid,$username,$password);
$studentList = $readFile->getStudentsOfPeriod($section);
$sectionList = $readFile->getSectionNames();
$assignmentIdMap1 = $readFile->getAssignmentMap($section);
$gMods = $readFile->lookUpCourseGradeItems($id, $assignmentIdMap1);
$gModMap = $readFile->getCourseGradeItemMap($id);

$combinedMap = array();

foreach ($assignmentIdMap1 as $key => $val)
{
    $combinedMap[$key] = $key;
}
foreach ($gModMap as $key => $val)
{
    $combinedMap[$key] = $key;
}
ksort($combinedMap);


echo <<<PAGE_HEAD
<html><body>

<p></p><ul>
<h3>School Loop Synchronization - Assignments</h3>

<p>Below are the assignments that are listed by Schoolloop</p>

<table>
    <col width="150">
    <col width="300">
    <col width="300">
   <tr valign="top"><td>Moodle Course:</td>
       <td colspan="2">$courseName_xx</td></tr>
   <tr valign="top"><td>Action:</td>
       <td colspan="2">Sync Students</td></tr>
   <tr valign="top"><td>Section:</td>
       <td colspan="2">$section : {$sectionList[$section]}</td></tr>
<form action="step2.php" method="get">
<input type="hidden" name="action"  value="Sync Assignments">
   <tr valign="top"><td></td>
       <td><input type="submit" value="Choose a Different Section"></td>
       <td></td></tr>
</form>
<form action="index.php" method="get">
<input type="hidden" name="id" value="$id">
   <tr valign="top"><td></td>
       <td><input type="submit" name="noaction" value="Start Over"></td>
       <td></td></tr>
</form>
</table>

<p>Below you see an alphabetical listing of all the assignments found in
   either Schoolloop or Moodle.  If the assignment
   matches up with a grade item in Moodle, details are included.
   To make assignments match, you may need to edit the name of the
   assignment in either Schoolloop or Moodle.</p>

<table cellpadding="3">
<tr>
  <td>Assignment Name</td>
  <td>Schoolloop ID</td>
  <td> &nbsp; &nbsp; </td>
  <td>Moodle Module & ID</td>
  <td></td>
</tr>
PAGE_HEAD;

foreach ($combinedMap as $key => $val) {
    $systemID = $assignmentIdMap1[$key];
    $assignment = $gModMap[$key];
    $msg = "";

    echo "<tr>\n";
    echo "<td>".htmlspecialchars($key)."</td>\n";
    if (isset($systemID)) {
        echo "<td>".htmlspecialchars($systemID)."</td>\n";
    }
    else {
        echo "<td> &nbsp; <font color=\"red\">missing</font></td>\n";
        $msg = "Grade will not be sent to Schoolloop";
    }
    echo "<td> &nbsp; &nbsp; </td>\n";
    if (isset($assignment)) {
        echo "<td>".htmlspecialchars($assignment['module'])." ".htmlspecialchars($assignment['id'])."</td>\n";
    }
    else {
        echo "<td> &nbsp; <font color=\"red\">missing</font></td>\n";
        $msg = "No grade for this exists in Moodle";
    }
    echo "<td> &nbsp; <font color=\"red\">$msg</font></td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

//print_tabley("Grade Items course $id (system id from $secid)", $gMods, array('id', 'module', 'name', 'systemID'));
//print_tabley("Grade Items course $id Map", $gModMap, array('id', 'module', 'name'));

echo <<<PAGE_FOOT

<p>If there are assignments above, which are supposed to match, for which an error message
   appears indicating a problem, either go to the definition of the assignment in Schoolloop,
   or the definition of the item in Moodle, and correct the name, or create a new item
   with the correct name.</p>
<hr/>
<pre>
PAGE_FOOT;

//this space reserved for debug stuff

echo("</pre></body></html>");





