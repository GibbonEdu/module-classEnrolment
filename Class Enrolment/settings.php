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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Class Enrolment/settings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $gibbon->session->get('absoluteURL').'/modules/Class Enrolment/settingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $setting = $settingGateway->getSettingByScope('Class Enrolment', 'openParentEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $col = $row->addColumn('openParentEnrolment')->setClass('inline');
        $col->addDate('openParentEnrolmentDate')->setValue(Format::date(substr($setting['value'], 0, 11)))->addClass('mr-2');
        $col->addTime('openParentEnrolmentTime')->setValue(substr($setting['value'], 11, 5));

    $setting = $settingGateway->getSettingByScope('Class Enrolment', 'closeParentEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $col = $row->addColumn('closeParentEnrolment')->setClass('inline');
        $col->addDate('closeParentEnrolmentDate')->setValue(Format::date(substr($setting['value'], 0, 11)))->addClass('mr-2');
        $col->addTime('closeParentEnrolmentTime')->setValue(substr($setting['value'], 11, 5));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
