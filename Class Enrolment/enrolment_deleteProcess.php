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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

if ($gibbonPersonID == '' or $gibbonCourseClassID == '') {
    echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/enrolment_delete.php&gibbonPersonID=$gibbonPersonID&gibbonCourseClassID=$gibbonCourseClassID";
    $URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/enrolment.php&gibbonPersonID=$gibbonPersonID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/enrolment_delete.php') == false) {
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
            $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);
            $courseEnrolment = $courseEnrolmentGateway->selectBy(['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID])->fetch();

            if (empty($courseEnrolment['gibbonCourseClassPersonID'])) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $updated = $courseEnrolmentGateway->update($courseEnrolment['gibbonCourseClassPersonID'], ['role' => 'Student - Left', 'dateUnenrolled' => date('Y-m-d')]);

                if (!$updated) {
                    $URL .= "&return=error2";
                    header("Location: {$URL}");
                } else {
                    $URLDelete .= '&return=success0';
                    header("Location: {$URLDelete}");
                }
            }
        }
    }
}
