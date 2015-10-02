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
 * This class is used to retrieve and display a range of
 * relationships that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Relationship_Selector extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  static $_columnHeaders;

  /**
   * Properties of relationship we're interested in displaying
   * @var array
   */
  static $_properties = array(
    'relationship_id',
    'contact_id_a',
    'contact_id-b',
    'relationship_type_id',
    'start_date',
    'end_date',
    'is_active',
    'description',
    'is_permission_a_b',
    'is_permission_b_a',
    'case_id',
    'contact_a',
    'contact_b',
    'relationship_type',
  );

  /**
   * FormValues is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_formValues;

  /**
   * The contextMenu
   *
   * @var array
   */
  protected $_contextMenu;

  /**
   * Params is the array in a value used by the search query creator
   *
   * @var array
   */
  public $_params;

  /**
   * The return properties used for search
   *
   * @var array
   */
  protected $_returnProperties;

  /**
   * Represent the type of selector
   *
   * @var int
   */
  protected $_action;

  protected $_searchContext;

  protected $_query;

  /**
   * The public visible fields to be shown to the user
   *
   * @var array
   */
  protected $_fields;

  /**
   * Class constructor.
   *
   * @param $customSearchClass
   * @param array $formValues
   *   Array of form values imported.
   * @param array $params
   *   Array of parameters for query.
   * @param null $returnProperties
   * @param \const|int $action - action of search basic or advanced.
   *
   * @param bool $includeRelationshipIds
   * @param string $searchContext
   * @param null $contextMenu
   *
   * @return CRM_Contact_Selector
   */
  public function __construct(
    $customSearchClass,
    $formValues = NULL,
    $params = NULL,
    $returnProperties = NULL,
    $action = CRM_Core_Action::NONE,
    $includeRelationshipIds = FALSE, $searchContext = 'search',
    $contextMenu = NULL
  ) {
    //don't build query constructor, if form is not submitted
    $force = CRM_Utils_Request::retrieve('force', 'Boolean', CRM_Core_DAO::$_nullObject);
    if (empty($formValues) && !$force) {
      return;
    }

    // submitted form values
    $this->_formValues = &$formValues;
    $this->_params = &$params;
    $this->_returnProperties = &$returnProperties;
    $this->_contextMenu = &$contextMenu;
    $this->_context = $searchContext;

    // type of selector
    $this->_action = $action;

    $this->_searchContext = $searchContext;

    $operator = CRM_Utils_Array::value('operator', $this->_formValues, 'AND');

    $this->_query = new CRM_Relationship_BAO_Query(
        $this->_params,
      $this->_returnProperties,
      NULL,
      $includeRelationshipIds, FALSE,
      CRM_Relationship_BAO_Query::MODE_RELATIONSHIPS, FALSE,
      //$searchDescendentGroups,
        FALSE,
      //$displayRelationshipType,
        $operator
    );

    $this->_options = &$this->_query->_options;
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   */
  public static function &links() {
    list($context, $contextMenu, $key) = func_get_args();
    $extraParams = ($key) ? "&key={$key}" : NULL;
    $searchContext = ($context) ? "&context=$context" : NULL;

    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/rel',
          'class' => 'no-popup',
          'qs' => "reset=1&action=view&cid=%%contact_id_a%%&id=%%id%%&rtype=a_b{$searchContext}{$extraParams}",
          'title' => ts('View Relationship Details'),
          'ref' => 'view-relationship',
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/rel',
          'class' => 'no-popup',
          'qs' => "reset=1&action=update&cid=%%contact_id_a%%&id=%%id%%&rtype=a_b{$searchContext}{$extraParams}",
          'title' => ts('Edit Relationship Details'),
          'ref' => 'edit-relationship',
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Contact %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * @param null $action
   * @param null $output
   *
   * @return array
   */
  public function &getColHeads($action = NULL, $output = NULL) {
    $colHeads = self::_getColumnHeaders();
    $colHeads[] = array('desc' => ts('Actions'), 'name' => ts('Action'));
    return $colHeads;
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    $headers = NULL;

    if ($output == CRM_Core_Selector_Controller::EXPORT) {
      $csvHeaders = array(ts('Relationship ID'), ts('Relationship Type'));
      foreach ($this->getColHeads($action, $output) as $column) {
        if (array_key_exists('name', $column)) {
          $csvHeaders[] = $column['name'];
        }
      }
      $headers = $csvHeaders;
    }
    elseif ($output == CRM_Core_Selector_Controller::SCREEN) {
      $csvHeaders = array(ts('Relationship ID'));
      foreach ($this->getColHeads($action, $output) as $key => $column) {
        if (array_key_exists('relationship_id', $column) &&
            $column['relationship_id'] &&
            $column['relationship_id'] != ts('Relationship ID')
        ) {
          $csvHeaders[$key] = $column['relationship_id'];
        }
      }
      $headers = $csvHeaders;
    }
    elseif (!empty($this->_returnProperties)) {
      self::$_columnHeaders = array(
        array('relationship_id' => ''),
        array(
          'relationship_id' => ts('Relationship ID'),
          'sort' => 'relationship_id',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
      );
      $properties = self::makeProperties($this->_returnProperties);

      foreach ($properties as $prop) {
        if (isset($this->_query->_fields[$prop]) && isset($this->_query->_fields[$prop]['relationship_id'])) {
          $relationship_id = $this->_query->_fields[$prop]['relationship_id'];
        }
        else {
          $relationship_id = '';
        }

        self::$_columnHeaders[] = array('relationship_id' => $relationship_id, 'sort' => $prop);
      }
      self::$_columnHeaders[] = array('name' => ts('Actions'));
      $headers = self::$_columnHeaders;
    }
    else {
      $headers = $this->getColHeads($action, $output);
    }

    return $headers;
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
    // Use count from cache during paging/sorting
    if (!empty($_GET['crmPID']) || !empty($_GET['crmSID'])) {
      $count = CRM_Core_BAO_Cache::getItem('Search Results Count', $this->_key);
    }
    if (empty($count)) {
      $count = $this->_query->searchQuery(0, 0, NULL, TRUE);
      CRM_Core_BAO_Cache::setItem($count, 'Search Results Count', $this->_key);
    }
    return $count;
  }

  /**
   * Returns all the rows in the given offset and rowCount.
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return int
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $config = CRM_Core_Config::singleton();

    if (($output == CRM_Core_Selector_Controller::EXPORT ||
        $output == CRM_Core_Selector_Controller::SCREEN
      ) &&
      $this->_formValues['radio_ts'] == 'ts_sel'
    ) {
      $includeRelationshipIds = TRUE;
    }
    else {
      $includeRelationshipIds = FALSE;
    }

    // note the formvalues were given by CRM_Relationship_Form_Search to us
    // and contain the search criteria (parameters)
    // note that the default action is basic
    if ($rowCount) {
      $cacheKey = $this->buildPrevNextCache($sort);
      $result = $this->_query->getCachedRelationships($cacheKey, $offset, $rowCount, $includeRelationshipIds);
    }
    else {
      $result = $this->_query->searchQuery($offset, $rowCount, $sort, FALSE, $includeRelationshipIds);
    }

    // process the result of the query
    $rows = array();
    $permissions = array(CRM_Core_Permission::getPermission());
    $mask = CRM_Core_Action::mask($permissions);

    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    if (!empty($this->_returnProperties)) {
      $names = self::makeProperties($this->_returnProperties);
    }
    else {
      $names = self::$_properties;
    }

    $links = self::links($this->_context, $this->_contextMenu, $this->_key);
    $seenIDs = array();
    while ($result->fetch()) {
      $row = array();

      // the columns we are interested in
      foreach ($names as $property) {

        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($property)) {
          $row[$property] = CRM_Core_BAO_CustomField::getDisplayValue(
            $result->$property,
            $cfID,
            $this->_options,
            $result->relationship_id
          );
        }
        else {
          $row[$property] = isset($result->$property) ? $result->$property : NULL;
        }
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT) {
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->relationship_id;

        $row['action'] = CRM_Core_Action::formLink(
            $links,
            $mapMask,
            array('id' => $result->relationship_id), ts('more'),
            FALSE,
            'relationship.selector.row', 'Relationship', $result->relationship_id
        );
        

        // allow components to add more actions
        CRM_Core_Component::searchAction($row, $result->relationship_id);

        $row['relationship_id'] = $result->relationship_id;

        if (array_key_exists('id', $row)) {
          $row['id'] = $result->relationship_id;
        }
      }

      // Dedupe contacts
      if (in_array($row['relationship_id'], $seenIDs) === FALSE) {
        $seenIDs[] = $row['relationship_id'];
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * @param CRM_Utils_Sort $sort
   *
   * @return string
   */
  public function buildPrevNextCache($sort) {
    $cacheKey = 'civicrm search ' . $this->_key;

    // We should clear the cache in following conditions:
    // 1. when starting from scratch, i.e new search
    // 2. if records are sorted

    // get current page requested
    $pageNum = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

    // get the current sort order
    $currentSortID = CRM_Utils_Request::retrieve('crmSID', 'String', CRM_Core_DAO::$_nullObject);

    $session = CRM_Core_Session::singleton();

    // get previous sort id
    $previousSortID = $session->get('previousSortID');

    // check for current != previous to ensure cache is not reset if paging is done without changing
    // sort criteria
    if (!$pageNum || (!empty($currentSortID) && $currentSortID != $previousSortID)) {
      CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey, 'civicrm_relationship');
      // this means it's fresh search, so set pageNum=1
      if (!$pageNum) {
        $pageNum = 1;
      }
    }

    // set the current sort as previous sort
    if (!empty($currentSortID)) {
      $session->set('previousSortID', $currentSortID);
    }

    $pageSize = CRM_Utils_Request::retrieve('crmRowCount', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, 50);
    $firstRecord = ($pageNum - 1) * $pageSize;

    //for text field pagination selection save
    $countRow = CRM_Core_BAO_PrevNextCache::getCount($cacheKey, NULL, "entity_table = 'civicrm_contact'");
    if ($firstRecord >= $countRow) {
      $this->fillupPrevNextCache($sort, $cacheKey, $countRow, 500 + $firstRecord - $countRow);
    }
    return $cacheKey;
  }

  /**
   * @param $rows
   */
  public function addActions(&$rows) {
    $config = CRM_Core_Config::singleton();

    $permissions = array(CRM_Core_Permission::getPermission());

    $mask = CRM_Core_Action::mask($permissions);
    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    // mask value to hide map link if there are not lat/long
    $mapMask = $mask & 4095;

    $links = self::links($this->_context, $this->_contextMenu, $this->_key);

    foreach ($rows as $id => & $row) {

        $row['action'] = CRM_Core_Action::formLink(
          $links,
          $mapMask,
          array('id' => $row['relationship_id']), ts('more'),
          FALSE,
          'relationship.selector.actions', 'Relationship', $row['relationship_id']
      );
      

      // allow components to add more actions
      CRM_Core_Component::searchAction($row, $row['relationship_id']);
    }
  }

  /**
   * @param $rows
   */
  public function removeActions(&$rows) {
    foreach ($rows as $rid => & $rValue) {
      unset($rValue['action']);
    }
  }

  /**
   * @param CRM_Utils_Sort $sort
   * @param string $cacheKey
   * @param int $start
   * @param int $end
   */
  public function fillupPrevNextCache($sort, $cacheKey, $start = 0, $end = 500) {

    // For core searches use the searchQuery method
    $sql = $this->_query->searchQuery($start, $end, $sort, FALSE, $this->_query->_includeRelationshipIds, FALSE, TRUE, TRUE);
    $replaceSQL = "SELECT relationship.id as id";


    // CRM-9096
    // due to limitations in our search query writer, the above query does not work
    // in cases where the query is being sorted on a non-contact table
    // this results in a fatal error :(
    // see below for the gross hack of trapping the error and not filling
    // the prev next cache in this situation
    // the other alternative of running the FULL query will just be incredibly inefficient
    // and slow things down way too much on large data sets / complex queries

    $insertSQL = "
INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data )
SELECT DISTINCT 'civicrm_relationship', relationship.id, relationship.id, '$cacheKey', relationship.id
";

    $sql = str_replace($replaceSQL, $insertSQL, $sql);

    $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    $result = CRM_Core_DAO::executeQuery($sql);
    unset($errorScope);

    if (is_a($result, 'DB_Error')) {
      // check if we get error during core search
      if ($coreSearch) {
        // in the case of error, try rebuilding cache using full sql which is used for search selector display
        // this fixes the bugs reported in CRM-13996 & CRM-14438
        $this->rebuildPreNextCache($start, $end, $sort, $cacheKey);
      }
      else {
        // return if above query fails
        return;
      }
    }

    // also record an entry in the cache key table, so we can delete it periodically
    CRM_Core_BAO_Cache::setItem($cacheKey, 'CiviCRM Search PrevNextCache', $cacheKey);
  }

  /**
   * called to rebuild prev next cache using full sql in case of core search ( excluding custom search)
   *
   * @param int $start
   *   Start for limit clause.
   * @param int $end
   *   End for limit clause.
   * @param CRM_Utils_Sort $sort
   * @param string $cacheKey
   *   Cache key.
   *
   * @return void
   */
  public function rebuildPreNextCache($start, $end, $sort, $cacheKey) {
    // generate full SQL
    $sql = $this->_query->searchQuery($start, $end, $sort, FALSE, $this->_query->_includeRelationshipIds, FALSE, FALSE, TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    // build insert query, note that currently we build cache for 500 contact records at a time, hence below approach
    $insertValues = array();
    while ($dao->fetch()) {
      $insertValues[] = "('civicrm_relationship', {$dao->relationship_id}, {$dao->relationship_id}, '{$cacheKey}', '" . CRM_Core_DAO::escapeString($dao->relationship_id) . "')";
    }

    //update pre/next cache using single insert query
    if (!empty($insertValues)) {
      $sql = 'INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data ) VALUES
' . implode(',', $insertValues);

      $result = CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * @inheritDoc
   */
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('CiviCRM Relationship Search');
  }

  /**
   * Get colunmn headers for search selector.
   *
   * @return array
   */
  private static function &_getColumnHeaders() {
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = array(
        array(
          'name' => ts('Contact A'),
          'sort' => 'contact_a.sort_name',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Type'),
          'sort' => 'relationship_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Contact B'),
          'sort' => 'contact_b.sort_name',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Start Date'),
          'sort' => 'relationship_start_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('End Date'),
          'sort' => 'relationship_end_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Status'),
          'sort' => 'relationship_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('Actions')),
      );
    }
    return self::$_columnHeaders;
  }

  /**
   * @return CRM_Relationship_BAO_Query
   */
  public function &getQuery() {
    return $this->_query;
  }


  /**
   * @param array $params
   * @param $action
   * @param int $sortID
   * @param string $queryOperator
   *
   * @return CRM_Relationship_DAO_Relationship
   */
  public function contactIDQuery($params, $action, $sortID, $queryOperator = 'AND') {
    $sortOrder = &$this->getSortOrder($this->_action);
    $sort = new CRM_Utils_Sort($sortOrder, $sortID);

    
      $query = new CRM_Relationship_BAO_Query($params, $this->_returnProperties, NULL, FALSE, FALSE, 1,
        FALSE, TRUE, TRUE, //$displayRelationshipType,
        $queryOperator
      );
    
    $value = $query->searchQuery(0, 0, $sort,
      FALSE, FALSE, FALSE,
      FALSE, FALSE
    );
    return $value;
  }
}
