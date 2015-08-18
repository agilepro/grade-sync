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


$id  = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);
require_capability('moodle/grade:export', $context);
require_capability('gradeexport/sl:view', $context);

$loggedinuser = $USER->username;

require_once 'sl_config.php';
$myapiconfig = $url = $SLCONFIG[$loggedinuser];
$url       = $myapiconfig['url'];
$teacherid = $myapiconfig['teacherid'];
$username  = $myapiconfig['apiuser'];
$password  = $myapiconfig['apipass'];
$_SESSION['sl_course'] = $id;
$_SESSION['sl_url'] = $url;
$_SESSION['sl_teacherid'] = $teacherid;
$_SESSION['sl_username'] = $username;
$_SESSION['sl_password'] = $password;


$url_xx       = htmlspecialchars($url);
$teacherid_xx = htmlspecialchars($teacherid);

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}

$courseName = $course->fullname;

echo <<<PAGE_HEAD
<html><body>

<p></p>
<h3>School Loop Synchronization - Step 1</h3>

<p>This module offers the capability to synchronize the students and the
   grades between Moodle and Schoolloop.  There are three main capabilities:</p>
<ol>
   <li>Display a list of students from both Moodle and Schoolloop, and indicate
       which ones are matched, and which exist only in one and not the other.
       Failure to find a match is often because the student id was typed incorrectly.</li>
   <li>Display a list of grade items from Moodle and assignments from Schoolloop and
       to show which ones are matched, and which exist without a corresponding counterpart
       in the other system.  Grades can be synchronized only between matched items.
       The matching is done by assignment name, and failure to match might be caused
       by an unintentional change in the name.</li>
   <li>Send grades earned in Moodle assignements/quizzes to Schoolloop for reporting
       to students and parents, and for integration with grades earned in other ways.</li>
</ol>
<p></p>
PAGE_HEAD;

if (isset($myapiconfig)) {

echo <<<PAGE_OKAY
<p><b>Your account ($loggedinuser) is configured to access the Schoolloop site at
   <a href="$url_xx">$url_xx</a>.</b></p>

<table>
    <col width="150">
    <col width="400">
<form action="step1Action.php" method="post">
<input type="hidden" name="id" value="$id">
   <tr valign="top"><td>Moodle Course:</td>
       <td colspan>$courseName</td></tr>
   <tr valign="top"><td></td>
       <td colspan><input type="submit" name="action" value="Sync Students">
           <input type="submit" name="action" value="Sync Assignments">
           <input type="submit" name="action" value="Sync Grades"></td>
       </tr>
</form>
</table>

PAGE_OKAY;

}
else {

echo <<<PAGE_NOT_OKAY
<p><b><i>Your account ($loggedinuser) not is configured to access the Schoolloop.
   To enable the Schoolloop synchronization function, you will
   need to get help from the site administrator.
   The administrator will need to be given the following
   information, in order to enable your account:</i></b></p>

<table>
    <col width="150">
    <col width="400">
   <tr valign="top"><td>URL to Schoolloop:</td>
       <td colspan>This is the web URL to the base of the schoolloop site
                   that you wish to synchronize these Moodle results with.</td></tr>

   <tr valign="top"><td>SL Teacher ID:</td>
       <td>This is a number that Schoolloop assigns to each teacher.
           It must be provided so that the correct course/section roster is read.
           You might need help from you Schoolloop admin to find out
           what your Schoolloop teacher id is.</td></tr>

   <tr valign="top"><td>API Username:</td>
       <td>This is the user name to be used with the OpenLoop API.</td></tr>
   <tr valign="top"><td>API Password:</td>
       <td>This is NOT your normal password for logging into Schoolloop, but instead it is
           a special password specifically for accessing the OpenLoop API</td></tr>
</table>


PAGE_NOT_OKAY;

}

echo <<<PAGE_TAIL
<p></p>
<h3>Caveats</h3>
<ul>
    <li><b>User Interface:</b>  As you can see, this page is not currently using the standard
        Moodle mechanisms for user interface.  This will be addressed soon, but in the mean
        time this "bare bones" capability is usable.</li>
</ul>
</body></html>

PAGE_TAIL;



