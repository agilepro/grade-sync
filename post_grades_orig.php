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

$postfile  = required_param('postfile', PARAM_RAW);   // the contents to post
$postfile_xx = htmlspecialchars($postfile);

$ch = curl_init();
$fileURL = $url.'api/grades';
curl_setopt($ch, CURLOPT_URL, $fileURL);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD,"$username:$password");
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfile);
$result = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$result_xx = htmlspecialchars($result);
$resmsg = "There was a problem transferring the grades (#$curl_errno)";
if (substr($result,0,7)=="SUCCESS") {
    $resmsg = "Grades have been successfully transerred to Schoolloop";
    $result_xx = "";
}

echo <<<PAGE_HEAD
<html><body>

<h3>School Loop Synchronization - Posted Grades</h3>

<p>Post to $fileURL</p>

<h3>$resmsg</h3>

<p><font color="red">$result_xx</font></p>


<hr>
<h3>Data Posted</h3>
<pre>
$postfile_xx
</pre>
</body></html>
PAGE_HEAD;






