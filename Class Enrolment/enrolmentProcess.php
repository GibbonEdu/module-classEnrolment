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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

include '../../gibbon.php';

$gibbonCourseClassIDs = $_POST['gibbonCourseClassID'] ?? [];
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';

if ($gibbonPersonID == '' or count($gibbonCourseClassIDs) < 1) {
    echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/enrolment.php&gibbonPersonID=$gibbonPersonID";

    if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/enrolment.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        // Prepare array of family members
        $familyGateway = $container->get(FamilyGateway::class);
        $families = [];
        $familyMembers = [] ;
        $people = [];
        foreach ($familyGateway->selectFamiliesByAdult($session->get('gibbonPersonID'))->fetchAll() as $family) {
            $families[] = $family["gibbonFamilyID"];
        }
        $familyMembers = $familyGateway->selectAdultsByFamily($families)->fetchAll();
        $familyMembers = array_merge($familyMembers, $familyGateway->selectChildrenByFamily($families)->fetchAll());
        foreach ($familyMembers as $member) {
            $people[] = $member['gibbonPersonID'];
        }

        if (!in_array($gibbonPersonID, $people)) {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            $settingGateway = $container->get(SettingGateway::class);
            $courseGateway = $container->get(CourseGateway::class);
            $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

            $useDatabaseLocking = $settingGateway->getSettingByScope('Class Enrolment', 'useDatabaseLocking');

            // Lock database table, depending on useDatabaseLocking setting
            if ($useDatabaseLocking == "Y") {
                try {
                    $sql = 'LOCK TABLES gibbonCourseClassPerson WRITE, gibbonSchoolYear READ, gibbonCourse READ, gibbonCourseClass READ, gibbonPerson READ';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
            }

            // Insert/update enrolment records
            foreach ($gibbonCourseClassIDs AS $gibbonCourseClassID) {
                $course = $courseGateway->getCourseClassByID($gibbonCourseClassID);
                $currentCourseEnrolment = $courseEnrolmentGateway->getClassStudentCount($gibbonCourseClassID, false);
                if (is_numeric($course['enrolmentMax']) && $currentCourseEnrolment > $course['enrolmentMax']) {
                    $partialFail = true;
                }
                else {
                    $courseEnrolment = $courseEnrolmentGateway->selectBy(['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID])->fetch();
                    if (empty($courseEnrolment['role'])) { // INSERT
                        $inserted = $courseEnrolmentGateway->insert(['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID, 'role' => 'Student', 'dateEnrolled' => date('Y-m-d')]);
                        if (!$inserted) {
                            $partialFail = true;
                        }
                    } else if ($courseEnrolment['role'] != 'Student') { // UPDATE
                        $updated = $courseEnrolmentGateway->update($courseEnrolment['gibbonCourseClassPersonID'], ['role' => 'Student', 'dateEnrolled' => date('Y-m-d'), 'dateUnenrolled' => null]);
                        if (!$updated) {
                            $partialFail = true;
                        }
                    }
                }
            }

            // Unlock database table, depending on useDatabaseLocking setting
            if ($useDatabaseLocking == "Y") {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            }


            if ($partialFail) {
               $URL .= '&return=warning1';
               header("Location: {$URL}");
           } else {
               $URL .= '&return=success0';
               header("Location: {$URL}");
           }
        }
    }
}
