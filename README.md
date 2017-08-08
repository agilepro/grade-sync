# grade-sync
A facility for moving grades from Moodle to SchoolLoop
 
 
This all goes in moodle/grade/export/sl/

The sl_config.php is then modified for the teachers involved.   This contains a table with an entry for each teacher indexed by their user id that they log into moodle with.  It looks like this:

    {teacher's moodle login} => array(
        'url'       => {the URL for schoolloop ... same for evryone},
        'teacherid' => {the teacher ID in schoolloop}
        'apiuser'   => {a special schoolloop user account for dong the transfer, same for everyone},
        'apipass'   => {password for the apiuser, same for everyone}
    )
    
    
To set up a given teacher, first get their moodle login id.  Then the teacherid from Schoolloop.  The teacher id is not their login ID for schoolloop, it is a 6-digit number value that appears to the administrator and it can be hard to find.  Last I checked there was no way for a teacher to find out their own teacher id.   If you are all synchronizing to the same schoolloop, then the other three values will be the same for everyone.

