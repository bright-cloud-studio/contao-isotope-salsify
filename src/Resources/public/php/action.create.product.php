<?php

    // Initialize
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // Database Connection
    //$dbh = new mysqli("localhost", "staging_user", 'q&U,zA(+WUK$kQ!cZB', "staging_contao_5_3");
    //if ($dbh->connect_error) {
    //die("Connection failed: " . $dbh->connect_error);
    //}
    //$myfile = fopen("logs/admin_review_save_student_log_".date('m-d-Y_hia').".txt", "w") or die("Unable to open file!");
    
    // Get data from ajax
    //$assignment_id = $_POST['assignment_id'];
    //$student = $_POST['student'];
    
    //fwrite($myfile, "SAVING: Assignment ID: " . $assignment_id . "\r\n");
    //fwrite($myfile, "SAVING: Student: " . $student . "\r\n");
    
    //$update =  "update tl_assignment set student='".$student."' WHERE id='".$assignment_id."'";
    //$result_update = $dbh->query($update);
    
    //fwrite($myfile, "SAVING: Query Results: " . $result_update . "\r\n");
    
    //fclose($myfile);
    echo "success";
