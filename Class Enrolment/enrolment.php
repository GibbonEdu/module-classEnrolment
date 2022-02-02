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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Enrolment'));

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

        $form->setTitle(__('Select Child'));

        $children = $container->get(StudentGateway::class)->selectActiveStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchAll();
        $children = Format::nameListArray($children, 'Student', false, true);

        if (count($children) == 1) {
            $gibbonPersonID = array_key_first($children);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __m('Student'));
            $row->addSelectPerson('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])
                ->fromArray($children)
                ->selected($gibbonPersonID)
                ->placeholder();

		$row = $form->addRow();
            $row->addSubmit(__('Go'));

		echo $form->getOutput();

        if ($gibbonPersonID != '') {
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
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();

                if ($student['gibbonPersonID'] != $gibbonPersonID) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                }
                else {
                    // FORM
                    $form = Form::create('settings', $gibbon->session->get('absoluteURL').'/modules/Class Enrolment/enrolmentProcess.php');
                    $form->setTitle(__('Add Enrolment'));

                    $form->addHiddenValue('address', $gibbon->session->get('address'));
                    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

                    $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

                    $classes = array();
                    $enrolableClasses = $courseEnrolmentGateway->selectEnrolableClassesByYearGroup($session->get('gibbonSchoolYearID'), $student['gibbonYearGroupID'])->fetchAll();

                    if (!empty($enrolableClasses)) {
                        $classes['--'.__('Enrolable Classes').'--'] = Format::keyValue($enrolableClasses, 'gibbonCourseClassID', function ($item) {
                            $courseClassName = Format::courseClassName($item['course'], $item['class']);
                            $teacherName = Format::name('', $item['preferredName'], $item['surname'], 'Staff');

                            return $courseClassName .' - '. (!empty($teacherName)? $teacherName : '');
                        });
                    }

                    $row = $form->addRow();
                        $row->addLabel('gibbonCourseClassID', __('Classes'));
                        $row->addSelect('gibbonCourseClassID')->fromArray($classes)->selectMultiple();

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    echo $form->getOutput();

                    // CURRENT ENROLMENT TABLE
                    // Query
                    $criteria = $courseEnrolmentGateway->newQueryCriteria(true)
                        ->sortBy('roleSortOrder')
                        ->sortBy(['course', 'class'])
                        ->fromPOST();

                    $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByPerson($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

                    // Data Table
                    $table = DataTable::create('activities');
                    $table->setTitle(__('Current Enrolment'));

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
}
