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
   * @var string what operator should we use, AND or OR
   */
  protected $_operator;

  /**
   * list of valid contexts.
   *
   * @var array
   */
  static $_validContext = NULL;

  /**
   * Name of the selector to use.
   */
  static $_selectorName = 'CRM_Relationship_Selector';
  protected $_customSearchClass = NULL;

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
   * List of values used when we want to display other objects.
   *
   * @var array
   */
  static $_modeValues = NULL;
  protected $_modeValue;

  /**
   * The contextMenu.
   *
   * @var array
   */
  protected $_contextMenu;

  /**
   * @var string how to display the results. Should we display as
   *             contributons, members, cases etc
   */
  protected $_componentMode;

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    $this->_done = FALSE;

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_componentMode = CRM_Utils_Request::retrieve('component_mode', 'Positive', $this, FALSE, 1, $_REQUEST);
    $this->_operator = CRM_Utils_Request::retrieve('operator', 'String', $this, FALSE, 1, $_REQUEST, 'AND');

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->assign('actionButtonName', $this->_actionButtonName);

    // assign context to drive the template display, make sure context is valid
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    if (!CRM_Utils_Array::value($this->_context, self::validContext())) {
      $this->_context = 'search';
    }
    $this->set('context', $this->_context);
    $this->assign("context", $this->_context);

    $this->_modeValue = self::getModeValue($this->_componentMode);
    $this->assign($this->_modeValue);
    $this->set('selectorName', self::$_selectorName);


    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->normalizeFormValues();
      $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
      $this->_returnProperties = &$this->returnProperties();

      // also get the object mode directly from the post value
      $this->_componentMode = CRM_Utils_Array::value('component_mode', $_POST, $this->_componentMode);

      // also get the operator from the post value if set
      $this->_operator = CRM_Utils_Array::value('operator', $_POST, $this->_operator);
      $this->_formValues['operator'] = $this->_operator;
      $this->set('operator', $this->_operator);
    }
    else {
      $this->_formValues = $this->get('formValues');
      $this->_params = CRM_Relationship_BAO_Query::convertFormValues($this->_formValues);
      $this->_returnProperties = &$this->returnProperties();
    }

    if (empty($this->_formValues)) {
      if (isset($this->_componentMode)) {
        $this->_formValues['component_mode'] = $this->_componentMode;
      }
      if (isset($this->_operator)) {
        $this->_formValues['operator'] = $this->_operator;
      }
    }

    $operator = CRM_Utils_Array::value('operator', $this->_formValues, 'AND');
    $this->set('queryOperator', $operator);
    if ($operator == 'OR') {
      $this->assign('operator', ts('OR'));
    }
    else {
      $this->assign('operator', ts('AND'));
    }

    if (!isset($this->_componentMode)) {
      $this->_componentMode = CRM_Relationship_BAO_Query::MODE_RELATIONSHIPS;
    }
    $modeValues = self::getModeValue($this->_componentMode);

    self::$_selectorName = $this->_modeValue['selectorName'];

    $setDynamic = FALSE;
    if (strpos(self::$_selectorName, 'CRM_Relationship_Selector') !== FALSE) {
      $selector = new self::$_selectorName(
          $this->_customSearchClass, $this->_formValues, $this->_params, $this->_returnProperties, $this->_action, FALSE, $this->_context, $this->_contextMenu
      );
      $setDynamic = TRUE;
    }
    else {
      $selector = new self::$_selectorName(
          $this->_params, $this->_action, NULL, FALSE, NULL, "search", "advanced"
      );
    }

    $selector->setKey($this->controller->_key);

    $controller = new CRM_Relationship_Selector_Controller($selector, $this->get(CRM_Utils_Pager::PAGE_ID), $this->get(CRM_Utils_Sort::SORT_ID), CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::TRANSFER
    );
    $controller->setEmbedded(TRUE);
    $controller->setDynamicAction($setDynamic);

    if ($this->_force) {

      $this->postProcess();

      /*
       * Note that we repeat this, since the search creates and stores
       * values that potentially change the controller behavior. i.e. things
       * like totalCount etc
       */
      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID), $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Relationship_Selector_Controller($selector, $this->get(CRM_Utils_Pager::PAGE_ID), $sortID, CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::TRANSFER
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
    }

    $controller->moveFromSessionToTemplate();

  }

  /**
   * Normalize the form values to make it look similar to the advanced form values
   * this prevents a ton of work downstream and allows us to use the same code for
   * multiple purposes (queries, save/edit etc)
   *
   * @return void
   */
  public function normalizeFormValues() {
    $relationshipType = CRM_Utils_Array::value('relationship_type', $this->_formValues);

    if ($relationshipType && is_array($relationshipType)) {
      unset($this->_formValues['relationship_type']);
      foreach ($relationshipType as $key => $value) {
        $this->_formValues['relationship_type'][$value] = 1;
      }
    }
  }

  /**
   * @return NULL
   */
  public function &returnProperties() {
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * @param int $mode
   *
   * @return mixed
   */
  public static function getModeValue($mode = 1) {
    self::setModeValues();

    if (!array_key_exists($mode, self::$_modeValues)) {
      $mode = 1;
    }

    return self::$_modeValues[$mode];
  }

  public static function setModeValues() {
    if (!self::$_modeValues) {
      self::$_modeValues = array(
        1 => array(
          'selectorName' => self::$_selectorName,
          'selectorLabel' => ts('Relationships'),
          'taskFile' => 'CRM/common/searchResultTasks.tpl',
          'taskContext' => NULL,
          'resultFile' => 'CRM/Relationship/Form/Selector.tpl',
          'resultContext' => NULL,
          'taskClassName' => 'CRM_Relationship_Task',
        ),
      );
    }
  }

  /**
   * Define the set of valid contexts that the search form operates on.
   *
   * @return array
   *   the valid context set and the titles
   */
  public static function &validContext() {
    if (!(self::$_validContext)) {
      self::$_validContext = array(
        'search' => 'Search',
      );
    }
    return self::$_validContext;
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

    $this->_searchPane = CRM_Utils_Array::value('searchPane', $_GET);

    if (!$this->_searchPane || $this->_searchPane == 'basic') {
      CRM_Relationship_Form_Search_Criteria::basic($this);
    }

    $allPanes = array();
    $paneNames = array(
      ts('Custom Fields') => 'custom',
      ts('Contact A') => 'contact_a',
      ts('Contact B') => 'contact_b',
    );

    //check if there are any custom data searchable fields
    $extends = array('Relationship');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);

    // if no searchable fields unset panel
    if (empty($groupDetails)) {
      unset($paneNames[ts('Custom Fields')]);
    }

    //TODO: allow hook injected panes
    //$hookPanes = array();
    //CRM_Contact_BAO_Query_Hook::singleton()->registerAdvancedSearchPane($hookPanes);
    //$paneNames = array_merge($paneNames, $hookPanes);

    $this->_paneTemplatePath = array();
    foreach ($paneNames as $name => $type) {
      $allPanes[$name] = array(
        'url' => CRM_Utils_System::url('civicrm/rel/search', "snippet=1&searchPane=$type&qfKey={$this->controller->_key}"
        ),
        'open' => 'false',
        'id' => $type,
      );

      // see if we need to include this paneName in the current form
      if ($this->_searchPane == $type || !empty($_POST["hidden_{$type}"]) ||
          CRM_Utils_Array::value("hidden_{$type}", $this->_formValues)
      ) {
        $allPanes[$name]['open'] = 'true';
        CRM_Relationship_Form_Search_Criteria::$type($this);
        $template = ucfirst($type);
        $this->_paneTemplatePath[$type] = "CRM/Relationship/Form/Search/Criteria/{$template}.tpl";
      }
    }

    $this->assign('allPanes', $allPanes);
    if (!$this->_searchPane) {
      parent::buildQuickForm();
      $resources = CRM_Core_Resources::singleton();
      $resources->
          addScriptFile('be.chiro.civi.relationship', 'js/markrelationselector.js', 1, 'html-header');
      //remove original js file
      $searchformjspath = $resources->getUrl('civicrm', 'js/crm.searchForm.js', TRUE);
      unset(CRM_Core_Region::instance('html-header')->_snippets[$searchformjspath]);

      $permission = CRM_Core_Permission::getPermission();
      // some tasks.. what do we want to do with the selected contacts ?
      $tasks = CRM_Relationship_Task::permissionedTaskTitles($permission, CRM_Utils_Array::value('deleted_contacts', $this->_formValues));

      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', array('checked' => 'checked'));
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);

      $selectedRelationshipIds = array();
      $qfKeyParam = CRM_Utils_Array::value('qfKey', $this->_formValues);
      // We use ajax to handle selections only if the search results component_mode is set to "relationships"
      if ($qfKeyParam && ($this->get('component_mode') <= 1)) {
        $this->addClass('crm-ajax-selection-form');
        $qfKeyParam = "civicrm search {$qfKeyParam}";
        $selectedRelationshipIdsArr = CRM_Core_BAO_PrevNextCache::getSelection($qfKeyParam);
        $selectedRelationshipIds = array_keys($selectedRelationshipIdsArr[$qfKeyParam]);
      }

      $this->assign_by_ref('selectedRelationshipIds', $selectedRelationshipIds);

      $rows = $this->get('rows');

      if (is_array($rows)) {
        $this->addRowSelectors($rows);
      }
    }
    else {
      $this->assign('suppressForm', TRUE);
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */

  /**
   * @return string
   */
  public function getTemplateFileName() {
    if (!$this->_searchPane) {
      return parent::getTemplateFileName();
    }
    else {
      if (isset($this->_paneTemplatePath[$this->_searchPane])) {
        return $this->_paneTemplatePath[$this->_searchPane];
      }
      else {
        $name = ucfirst($this->_searchPane);
        return "CRM/Relationship/Form/Search/Criteria/{$name}.tpl";
      }
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

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->normalizeFormValues();
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_params = CRM_Relationship_BAO_Query::convertFormValues($this->_formValues);
    $this->_returnProperties = &$this->returnProperties();


    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    //for prev/next pagination
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

    if (array_key_exists($this->_searchButtonName, $_POST) ||
        ($this->_force && !$crmPID)
    ) {
      //reset the cache table for new search
      $cacheKey = "civicrm search {$this->controller->_key}";
      CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey);
    }

    //get the button name
    $buttonName = $this->controller->getButtonName();

    if (isset($this->_componentMode) && empty($this->_formValues['component_mode'])) {
      $this->_formValues['component_mode'] = $this->_componentMode;
    }

    if (isset($this->_operator) && empty($this->_formValues['operator'])) {
      $this->_formValues['operator'] = $this->_operator;
    }

    if (empty($this->_formValues['qfKey'])) {
      $this->_formValues['qfKey'] = $this->controller->_key;
    }

    $this->set('type', $this->_action);
    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_params);
    $this->set('returnProperties', $this->_returnProperties);

    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page
      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }
    else {
      $output = CRM_Core_Selector_Controller::SESSION;

      // create the selector, controller and run - store results in session
      $searchChildGroups = TRUE;

      $setDynamic = FALSE;

      if (strpos(self::$_selectorName, 'CRM_Relationship_Selector') !== FALSE) {
        $selector = new self::$_selectorName(
            $this->_customSearchClass, $this->_formValues, $this->_params, $this->_returnProperties, $this->_action, FALSE, $this->_context, $this->_contextMenu
        );
        $setDynamic = TRUE;
      }
      else {
        $selector = new self::$_selectorName(
            $this->_params, $this->_action, NULL, FALSE, NULL, "search", "advanced"
        );
      }

      $selector->setKey($this->controller->_key);

      // added the sorting  character to the form array
      $config = CRM_Core_Config::singleton();
      // do this only for contact search


      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID), $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Relationship_Selector_Controller($selector, $this->get(CRM_Utils_Pager::PAGE_ID), $sortID, CRM_Core_Action::VIEW, $this, $output
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
      $controller->run();
    }
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
