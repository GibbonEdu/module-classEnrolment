<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Basic variables
$name = "Class Enrolment";
$description = "A simple module for allowing parents to enrol their children in classes, with no appoval required.";
$entryURL = "enrolment.php";
$type = "Additional";
$category = "Learn";
$version = "1.0.00";
$author = "Ross Parker";
$url = "http://rossparker.org";

//Settings
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Class Enrolment', 'openParentEnrolment', 'Open Parent Enrolment', 'When should parents sign ups open?', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Class Enrolment', 'closeParentEnrolment', 'Close Parent Enrolment', 'When should parents sign ups close?', '');";

// Action rows
$actionRows[] = [
    'name'                      => 'Enrolment_myChildren',
    'precedence'                => '0',
    'category'                  => 'Enrolment',
    'description'               => 'Allows a parent to enrol their own children in academic classes.',
    'URLList'                   => 'enrolment.php,enrolment_delete.php',
    'entryURL'                  => 'enrolment.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Settings',
    'precedence'                => '0',
    'category'                  => 'Admin',
    'description'               => 'Control settings for the Class Enrolment module.',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];
