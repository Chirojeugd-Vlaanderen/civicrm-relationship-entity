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
 */

/**
 * Advanced search, extends basic search.
 */
class CRM_Relationship_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single relationship.
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single relationship.
   *
   * @var boolean
   */
  protected $_limit = NULL;

  protected $_defaults;

  /**
   * Prefix for the controller.
   */
  protected $_prefix = "relationship_";

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    // @todo - is this an error - $this->_defaults is used.
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');

    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    if ($this->_force) {
      $this->postProcess();
      $this->set('force', 0);
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Relationship_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Relationship_Selector_Search($this->_queryParams, $this->_action, NULL,
      $this->_single,
      $this->_limit,
      $this->_context
      );
    $prefix = NULL;
    if ($this->_context == 'basic' || $this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER,
      $prefix
      );

    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate(); 

    $this->assign('summary', $this->get('summary'));
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    CRM_Relationship_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $permission = CRM_Core_Permission::getPermission();

      $tasks = CRM_Relationship_Task::permissionedTaskTitles($permission);
      $this->addTaskMenu($tasks);
    }

  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post processing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submission is
   *        done.
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    $this->_formValues = $this->controller->exportValues($this->_name);

    $this->fixFormValues();

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_queryParams = CRM_Relationship_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Relationship_BAO_Query::convertFormValues($this->_formValues);

    $selector = new CRM_Relationship_Selector_Search($this->_queryParams, $this->_action, NULL, $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic' || $this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);

    $query = &$selector->getQuery();
    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * Use values from $_GET if force is set to TRUE.
   *
   * Note that this means that GET over-rides POST. This was a historical decision & the reasoning is not explained.
   */
  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $fromDate = CRM_Utils_Request::retrieve('start', 'Date', CRM_Core_DAO::$_nullObject
    );
    if ($fromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($fromDate);
      $this->_formValues['relationship_start_date_low'] = $this->_defaults['relationship_start_date_low'] = $date;
    }

    $toDate = CRM_Utils_Request::retrieve('end', 'Date', CRM_Core_DAO::$_nullObject
    );
    if ($toDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($toDate);
      $this->_formValues['relationship_start_date_high'] = $this->_defaults['relationship_start_date_high'] = $date;
    }

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this
    );

    //give values to default.
    $this->_defaults = $this->_formValues;
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Relationships');
  }

}
