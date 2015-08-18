<?php

// This file is part of Moodle Schoolloop Sync
// Author: Keith D Swenson
// Copyright 2011, All rights reserved.

mb_internal_encoding("UTF-8");
header('Content-type: text/html;charset=UTF-8');
session_start();

$myinput = $_GET["myinput"];
$myinput_xx = htmlspecialchars($myinput);

$lastVal = $_SESSION['sl_test'];
$lastVal_xx = htmlspecialchars($lastVal);

$_SESSION['sl_test'] = $myinput;


echo "<h3>Testing PHP ability to read and write values</h3>\n";
echo "<p>Received the value: ".$myinput_xx."</p>\n";
echo "<p>internal encoding = ".mb_internal_encoding()."</p>\n";
echo "<p>string length = ".strlen($myinput)."</p>\n";
echo "<p>last value = $lastVal_xx</p>\n";
echo <<<FORMDATA

<hr/>
<form action="test.php" method="get">
<input type="text" name="myinput" value="$myinput_xx">
<input type="submit" value="save">
</form>

FORMDATA;


