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

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}

$courseName = $course->fullname;

$action    = required_param('action', PARAM_RAW);   // password for the API

//these five parameters must be in session after this point
$id        = $_SESSION['sl_course'];
$url       = $_SESSION['sl_url'];
$teacherid = $_SESSION['sl_teacherid'];
$username  = $_SESSION['sl_username'];
$password  = $_SESSION['sl_password'];

$readFile = new sl_roster($DB,$url,$teacherid,$username,$password);

$nextPage = 'step2.php?action='.urlencode($action);
if ($action == 'Sync Students') {
    $nextPage = 'sync_students.php';
}
if ($action == 'Sync Grades') {
    $nextPage = 'pick_assignment.php';
}

Header( "HTTP/1.1 301 Moved Permanently" );
Header( "Location: $nextPage" );

echo "Your browser should <a href=\"$nextPage\">redirect to $nextPage</a> momentarily\n";
