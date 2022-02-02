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

use Gibbon\Domain\Students\StudentGateway;
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
        // CHECK ACCESS TO STUDENT
        $studentGateway = $container->get(StudentGateway::class);
        $students = $studentGateway->selectActiveStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->toDataSet();
        $checkCount = false;
        foreach ($students as $student) {
            if ($student['gibbonPersonID'] == $gibbonPersonID) {
                $checkCount = true;
            }
        }

        if (!$checkCount) {
            $URL .= '&return=error0';
        } else {
            $partialFail = false;

            $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

            foreach  ($gibbonCourseClassIDs AS $gibbonCourseClassID) {
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
