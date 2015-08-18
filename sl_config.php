<?php


// This file has to contain all of the configuration parameters that allow a
// teacher to connect to and exchange information with Schoolloop.
// Each teacher can have one schoolloop site configured.
// Each teacher will only be able to synchronize with that one Schoolloop site.
//
// The main key is the teacher's login id.  The code uses the id of the person
// logged in, in order to find the rest of the values.  If there is no entry
// for a particular teacher, then they will not be able to synchronize.
//
// The main key gets four values from an array:
//
// url:     the base URL of the Schoolloop site
// teachid: the Schoolloop teacher id of this teacher
// apiuser: the user id for using the API
// apipass: the password for using the API

$SLCONFIG = array(

    'keith' => array(

        'url'       => 'http://ods-ousd-ca.schoolloop.com/',
        'teacherid' => '517',
        'apiuser'   => 'admin',
        'apipass'   => 'pirates' ),

    'def' => array(

        'url'       => 'http://ods-ousd-ca.schoolloop.com/',
        'teacherid' => '517',
        'apiuser'   => 'admin',
        'apipass'   => 'pirates' )

    );

?>


