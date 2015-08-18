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
$teacherid_xx = htmlspecialchars($teacherid);

$readFile = new sl_roster($DB,$url,$teacherid,$username,$password);
$assignmentIdMap1 = $readFile->getAllAssignmentMap();
$gModMap = $readFile->getCourseGradeItemMap($id);

$intersectionMap = array();

foreach ($assignmentIdMap1 as $key => $val)
{
    if (array_key_exists($key, $gModMap))
    {
        $intersectionMap[$key] = $key;
    }
}
ksort($combinedMap);



echo <<<PAGE_HEAD
<html><body>

<p></p><ul>
<h3>School Loop Synchronization - Select Section</h3>

<p>The next step is to pick the course section from schoolloop that you want
   to synchronize assignments/grades.</p>

<table>
<table>
    <col width="150">
    <col width="600">
<form action="sync_grades.php" method="get">
   <tr valign="top"><td>Moodle Course:</td>
       <td colspan="2">$courseName_xx</td></tr>
   <tr valign="top"><td>Teacher Id:</td>
       <td colspan="2">$teacherid_xx</td></tr>
   <tr valign="top"><td>Action:</td>
       <td colspan="2">Sync Grades</td></tr>
   <tr valign="top"><td>Select Assignment</td>
       <td>
PAGE_HEAD;


$sections = $readFile->getSectionNames();

$count = 0;
foreach ($intersectionMap as $key => $val)
{
    $count++;
    echo "<input type=\"checkbox\" name=\"pick$count\" value=\"$key\"> $key <br/>\n";
}
echo <<<PAGE_FOOT
       </td></tr>
   <tr valign="top"><td></td>
       <td><input type="submit" name="action" value="Go to Sync Grades"></td>
       <td></td></tr>
</form>
<form action="index.php" method="get">
<input type="hidden" name="id" value="$id">
   <tr valign="top"><td></td>
       <td><input type="submit" name="noaction" value="Start Over"></td>
       <td></td></tr>
</form>
</table>

<p></p>
<hr/>

PAGE_FOOT;


//print_tablez("Sections from Schoolloop", $readFile->getSectionNames());

//echo "<pre>";
//print_r($readFile->getCourses());
//echo "</pre>";

echo("</body></html>");





