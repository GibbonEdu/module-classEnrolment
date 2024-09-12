<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
            header("Location: {$URLDelete}");
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
