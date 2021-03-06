<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * class to represent the actions that can be performed on a group of
 * relationships used by the search forms
 *
 */
class CRM_Relationship_Task {

  const EXPORT_RELATIONSHIPS = 1;

  /**
   * The task array
   *
   * @var array
   */
  static $_tasks = NULL;

  /**
   * The optional task array
   *
   * @var array
   */
  static $_optionalTasks = NULL;

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a relationship / group of relationships
   *
   * @return array
   *   the set of tasks for a group of relationships
   */
  public static function &tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        1 => array(
          'title' => ts('Export Relationships'),
          'class' => array(
            'CRM_Relationship_Export_Form_Select',
            'CRM_Relationship_Export_Form_Map',
          ),
          'result' => FALSE,
        ),
      );

      CRM_Utils_Hook::searchTasks('relationship', self::$_tasks);
      asort(self::$_tasks);
    }
    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles
   * on relationships
   *
   * @return array
   *   the set of task titles
   */
  public static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      $titles[$id] = $value['title'];
    }
    return $titles;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function &permissionedTaskTitles($permission) {

    $tasks = array();

    if (($permission == CRM_Core_Permission::EDIT) || CRM_Core_Permission::check('edit contacts')
    ) {
      $tasks = self::taskTitles();
    }

    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on relationships
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of relationships
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
    // make the export task by default
      $value = 1;
    }
    // this is possible since hooks can inject a task
    // CRM-13697
    if (!isset(self::$_tasks[$value]['result'])) {
      self::$_tasks[$value]['result'] = NULL;
    }
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }

}
