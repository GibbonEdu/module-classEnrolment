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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/enrolment_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonPersonID and gibbonCourseClassID specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    if ($gibbonPersonID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
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
        if ($session->get('gibbonPersonID') == $gibbonPersonID) {
            $checkCount = true;
        }

        if (!$checkCount) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        }
        else {
            //Let's go!
            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/enrolment_deleteProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonPersonID=$gibbonPersonID");
            echo $form->getOutput();
        }
    }
}
