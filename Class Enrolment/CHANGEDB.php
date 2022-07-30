<?php
//USE ;end TO SEPARATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v1.0.00
$sql[$count][0] = "1.0.00";
$sql[$count][1] = "-- First version, nothing to update";
$count++;

//v1.0.01
$sql[$count][0] = "1.0.01";
$sql[$count][1] = "";
$count++;

//v1.0.02
$sql[$count][0] = "1.0.02";
$sql[$count][1] = "";
$count++;

//v1.1.00
$sql[$count][0] = "1.1.00";
$sql[$count][1] = "";
$count++;

//v1.1.01
$sql[$count][0] = "1.1.01";
$sql[$count][1] = "";
$count++;

//v1.2.00
$sql[$count][0] = "1.2.00";
$sql[$count][1] = "
INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Class Enrolment', 'useDatabaseLocking', 'Use Database Locking', 'Ensures fidelity of minimum and maximum enrolment, but comes with a performance cost.', 'Y');end
";
$count++;
