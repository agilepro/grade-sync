<?php

// This file is part of Moodle Schoolloop Sync
// Author: Keith D Swenson
// Copyright 2011, All rights reserved.

mb_internal_encoding("UTF-8");
header('Content-type: text/html;charset=UTF-8');

//$url = "http://bobcat:8080/wu/samples/SampleRoster.xml";
$url = $_GET['url'];
$username = $_GET['username'];
$password = $_GET['password'];

echo "<html><body>\n";
echo "<h3>Page Source Fetcher</h3>\n";
echo "<form action=\"fetchfile.php\">\n";
echo "  URL <input type=\"text\" name=\"url\" value=\"$url\" size=\"50\"><br/>\n";
echo "  UserName <input type=\"text\" name=\"username\" value=\"$username\"><br/>\n";
echo "  Password <input type=\"text\" name=\"password\" value=\"$password\"><br/>\n";
echo "  <input type=\"submit\" name=\"action\" value=\"Fetch File\">\n";
echo "</form>\n<hr/>\n";

if (isset($url)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_USERPWD,"$username:$password");
    $body = curl_exec($ch);
    $encodedBody = htmlspecialchars($body);
    $info = curl_getinfo ($ch);
}
else {
    $encodedBody = "nothing to show";
    $info = "no parameters to display";
}


echo "<pre>";
echo $encodedBody;
echo "</pre>\n<hr/>\n<pre>";
print_r($info);
echo "</pre></body></html>";

