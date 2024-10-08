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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__m('Enrolment'));

    $settingGateway = $container->get(SettingGateway::class);

    $now = date('Y-m-d H:i');
    $openParentEnrolment = $settingGateway->getSettingByScope('Class Enrolment', 'openParentEnrolment');
    $closeParentEnrolment = $settingGateway->getSettingByScope('Class Enrolment', 'closeParentEnrolment');

    if (!empty($openParentEnrolment) && !empty($closeParentEnrolment) && $openParentEnrolment >= $closeParentEnrolment) { // Invalid dates
        $page->addError(__m('The settings for class enrolments are invalid'));
    } else if ((!empty($openParentEnrolment) && $now < $openParentEnrolment) || (!empty($closeParentEnrolment) && $now > $closeParentEnrolment) || ($now < $openParentEnrolment && $now > $closeParentEnrolment)) { // Closed
        if ((!empty($openParentEnrolment) && $now < $openParentEnrolment)) {
            $page->addWarning(__m('The window for adding and editing class enrolments will open at {time} on {date}.', ['date' => Format::date(substr($openParentEnrolment, 0, 11)), 'time' => substr($openParentEnrolment, 11, 5)]));
        } else {
            $page->addWarning(__m('The window for adding and editing class enrolments is currently closed.'));
        }
    } else { // Open
        if ((!empty($closeParentEnrolment) && $now < $closeParentEnrolment)) {
            $page->addMessage(__m('The window for adding and editing class enrolments is currently open, but will close at {time} on {date}.', ['date' => Format::date(substr($closeParentEnrolment, 0, 11)), 'time' => substr($closeParentEnrolment, 11, 5)]));
        } else {
            $page->addMessage(__m('The window for adding and editing class enrolments is currently open.'));
        }

        // SELECT STUDENT
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

		$form = Form::create('selectFamily', $session->get('absoluteURL').'/index.php', 'get');
		$form->addHiddenValue('q', '/modules/'.$session->get('module').'/enrolment.php');

        $form->setTitle(__m('Select Student'));

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
            $people[$member['gibbonPersonID']] = Format::name('', $member['preferredName'], $member['surname'], 'Student', true, true);
        }
        asort($people);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __m('Student'));
            $row->addSelectPerson('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])
                ->fromArray($people)
                ->selected($gibbonPersonID)
                ->placeholder();

		$row = $form->addRow();
            $row->addSubmit(__('Go'));

		echo $form->getOutput();

        if ($gibbonPersonID != '') {
            // CHECK ACCESS TO STUDENT
            if (!array_key_exists($gibbonPersonID, $people)) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $studentGateway = $container->get(StudentGateway::class);
                $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID, false)->fetch();

                // FORM
                $form = Form::create('settings', $gibbon->session->get('absoluteURL').'/modules/Class Enrolment/enrolmentProcess.php');
                $form->setTitle(__m('Add Enrolment'));

                $form->addHiddenValue('address', $gibbon->session->get('address'));
                $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

                $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

                $classes = array();
                $enrolableClasses = $courseEnrolmentGateway->selectEnrolableClassesByYearGroup($session->get('gibbonSchoolYearID'), $student['gibbonYearGroupID'])->fetchAll();

                $disabledClasses = [];
                if (!empty($enrolableClasses)) {
                    $classes['--'.__m('Enrolable Classes').'--'] = Format::keyValue($enrolableClasses, 'gibbonCourseClassID', function ($item) {
                        $courseClassName = $item['courseName']." (Class ".$item['class'].")";
                        $teacherName = Format::name('', $item['preferredName'], $item['surname'], 'Staff');

                        return $courseClassName.(!empty($teacherName)? ' - '.$teacherName : '').(is_numeric($item['enrolmentMax']) && $item['studentCount'] > $item['enrolmentMax'] ? " (".__m('Full').")" : '');
                    });

                    // Determine which classes to disable based on enrolmentMax
                    foreach ($enrolableClasses as $item) {
                        if (is_numeric($item['enrolmentMax']) && $item['studentCount'] > $item['enrolmentMax']) {
                            $disabledClasses[] = $item['gibbonCourseClassID'];
                        }
                    }
                }

                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassID', __('Classes'));
                    $row->addSelect('gibbonCourseClassID')->fromArray($classes)->selectMultiple()->required();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();

                // Create JS to disable classes based on enrolmentMax
                echo "<script type='text/javascript'>";
                    echo '$(document).ready(function(){';
                        foreach ($disabledClasses as $item) {
                            echo "$(\"#gibbonCourseClassID option[value='".$item."']\").attr(\"disabled\",\"disabled\");";
                        }
                    echo '});';
                echo '</script>';

                // CURRENT ENROLMENT TABLE
                // Query
                $criteria = $courseEnrolmentGateway->newQueryCriteria(true)
                    ->sortBy('roleSortOrder')
                    ->sortBy(['course', 'class'])
                    ->fromPOST();

                $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByPerson($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

                // Data Table
                $table = DataTable::create('activities');
                $table->setTitle(__m('Current Enrolment'));

                $table->addColumn('courseClass', __('Class Code'))
                      ->sortable(['course', 'class'])
                      ->format(Format::using('courseClassName', ['course', 'class']));
                $table->addColumn('courseName', __('Course'));

                // ACTIONS
                $table->addActionColumn()
                    ->addParam('gibbonCourseClassID')
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->format(function ($class, $actions) {
                        $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/Class Enrolment/enrolment_delete.php');
                    });

                echo $table->render($enrolment);
            }
        }
    }
}
