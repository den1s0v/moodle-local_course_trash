<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Locale: ru (Russian).
 *
 * @package    local_course_trash
 * @copyright  2021 Marcelo Augusto Rauh Schmitt <marcelo.rauh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

$string['alert'] = 'ВНИМАНИЕ!!! Если вы продолжите эту операцию, то этот курс будет отправлен в корзину, откуда будет впоследствии удалён окончательно (обычно до этого момента пройдёт не менее 7 дней). <p> Вы потеряете доступ к курсу и его содержимому, включая все материалы, задания, ответы и оценки учащихся. <p> Если Вы позднее захотите восстановить этот курс, обратитесь к администратору вашего подразделения.';
// $string['alert_old'] = 'ВНИМАНИЕ!!! Если вы продолжите эту операцию, то этот курс будет ПОЛНОСТЬЮ и БЕЗВОЗВРАТНО удален, включая все материалы, задания, ответы и оценки учащихся. Подумайте о том, чтобы создать и скачать резервную копию курса (файл .mbz).';
$string['course_trash'] = 'Отправить курс в корзину';
$string['course_restore'] = 'Восстановить курс из корзины';
$string['course_trash:manage'] = 'Удалять свои курсы в корзину';
$string['deletedcourse'] = 'Курс перемещён в корзину: ';
$string['deletingcourse'] = 'Удаление курса в корзину';
$string['pluginname'] = 'Удаление курса в корзину';
$string['restoringcourse'] = 'Восстановление курса из корзины';
$string['restoredcourse'] = 'Курс восстановлен из корзины: ';



// settings-specific strings
$string['settings']            = 'Удаление курса в корзину';
$string['pluginname_desc']     = 'Плагин для безопасного удаления курсов в "корзину" (специальную категорию курсов "На удаление") для возможности восстановления случайно удалённых курсов. Фактическое удаление курсов будет производиться путём очистки указанной категории курсов.';
$string['enableplugin']        = 'Активировать плагин';
$string['enableplugin_help']   = 'Если выключено, возможности плагина недоступны';
$string['coursecat']        = 'Категория курсов';
$string['coursecat_help']   = 'Категория курсов "На удаление". Обычно при первоначальной настройке нужно создать новую категорию для этой цели.';

$string['heading_courseoperations']   = 'Манипуляции с курсом';
$string['heading_courseoperations_info']   = 'Какие изменения будут сделаны в курсе при отправке в корзину';
$string['movetocategory']        = 'Перенести в категорию "На удаление"';
$string['movetocategory_help']   = 'Если выключено, курс останется в исходном местоположении';
$string['hidecourse']        = 'Скрыть курс от студентов';
$string['hidecourse_help']   = 'Если выключено И если следующая настройка не предписывает заблокировать студентов, курс останется доступен студентам';

$string['suspend_anyone']        = 'всех';
$string['suspend_self_and_roles']= 'только себя и перечисленные роли';
$string['suspend_self_only']     = 'только себя';
$string['suspend_no_one']        = 'никого';
$string['suspendmode']        = 'Заблокировать…';
$string['suspendmode_help']   = 'Заблокировать участников курса согласно их ролям';

$string['suspendroles']        = 'Заблокировать роли';
$string['suspendroles_help']   = 'Заблокировать участников курса с этими ролями';
$string['set_enddate']        = 'Задать дату окончания курса';
$string['set_enddate_help']   = 'Установить дату окончания курса в текущую дату (это поможет впоследствии узнать, когда это действие было совершено)';
$string['saverestoredata']      = 'Сохранить данные для восстановления курса';
$string['saverestoredata_help'] = 'Если выключено, то данные об исходном местоположении и состоянии курса будут утрачены, и автоматическое восстановление курса будет невозможно. Сохранение производится в текст описания курса (summary).';



