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
 */
class CRM_Relationship_BAO_Query {

  /**
   * Static field for all the export/import relationship fields.
   *
   * @var array
   */
  static $_relationshipFields = NULL;

  /**
   * Are relationship ids part of the query.
   *
   * @var boolean
   */
  public $_includeRelationshipIds = FALSE;

  /**
   * The where string
   *
   * @var string
   */
  public $_whereClause;
  public $_sort;

  /**
   * Use distinct component clause for component searches
   *
   * @var string
   */
  public $_distinctComponentClause;
  public $_rowCountClause;

  /**
   * Reference to the query object for custom values.
   *
   * @var Object
   */
  public $_customQuery;

  /**
   * Should we enable the distinct clause, used if we are including
   * more than one group
   *
   * @var boolean
   */
  public $_useDistinct = FALSE;

  /**
   * Should we just display one contact record
   */
  public $_useGroupBy = FALSE;

  /**
   * The set of input params.
   *
   * @var array
   */
  public $_params;

  /**
   * The set of output params
   *
   * @var array
   */
  public $_returnProperties;

  /**
   * Are we in strict mode (use equality over LIKE)
   *
   * @var boolean
   */
  public $_strict = FALSE;

  /**
   * What operator to use to group the clauses.
   *
   * @var string
   */
  public $_operator = 'AND';

  /**
   * Should we skip permission checking.
   *
   * @var boolean
   */
  public $_skipPermission = FALSE;

  /**
   * All the fields that could potentially be involved in
   * this query
   *
   * @var array
   */
  public $_fields;

  /**
   * The select clause
   *
   * @var array
   */
  public $_select;

  /**
   * The name of the elements that are in the select clause
   * used to extract the values
   *
   * @var array
   */
  public $_element;

  /**
   * The tables involved in the query
   *
   * @var array
   */
  public $_tables;

  /**
   * The table involved in the where clause
   *
   * @var array
   */
  public $_whereTables;

  /**
   * The where clause
   *
   * @var array
   */
  public $_where;

  /**
   * The cache to translate the option values into labels.
   *
   * @var array
   */
  public $_options;
  public $_cfIDs;
  public $_paramLookup;

  /**
   * The having values
   *
   * @var string
   */
  public $_having;

  /**
   * The from string
   *
   * @var string
   */
  public $_fromClause;

  /**
   * The from clause for the simple select and alphabetical
   * select
   *
   * @var string
   */
  public $_simpleFromClause;

  /**
   * Class constructor which also does all the work.
   *
   * @param array $params
   * @param array $returnProperties
   * @param array $fields
   * @param bool $includeContactIds
   * @param bool $strict
   * @param bool|int $mode - mode the search is operating on
   *
   * @param bool $skipPermission
   * @param bool $searchDescendentGroups
   * @param bool $smartGroupCache
   * @param string $operator
   *
   * @return \CRM_Relationship_BAO_Query
   */
  public function __construct(
  $params = NULL, $returnProperties = NULL, $fields = NULL, $includeRelationshipIds = FALSE, $strict = FALSE, $skipPermission = FALSE, $operator = 'AND'
  ) {
    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = array();
    }

    if (empty($returnProperties)) {
      $this->_returnProperties = array();
    }
    else {
      $this->_returnProperties = &$returnProperties;
    }

    $this->_includeRelationshipIds = $includeRelationshipIds;
    $this->_strict = $strict;
    $this->_skipPermission = $skipPermission;
    //$this->setOperator($operator);

    if ($fields) {
      $this->_fields = &$fields;
      $this->_search = FALSE;
      $this->_skipPermission = TRUE;
    }
    else {
      $this->_fields = CRM_Contact_BAO_Relationship::fields();
      foreach ($this->_fields as $defaultFieldKey => $defaultField) {
        $this->_fields[$defaultFieldKey]['where'] = 'relationship.' . $defaultFieldKey;
      }
      // Add display_name for both contacts
      $contact_fields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE, TRUE);
      $this->_fields['contact_a'] = $contact_fields['display_name'];
      $this->_fields['contact_a']['where'] = 'contact_a.display_name';
      $this->_fields['contact_b'] = $contact_fields['display_name'];
      $this->_fields['contact_b']['where'] = 'contact_b.display_name';
      // Add relationship type field
      $relationship_type_fields = CRM_Contact_BAO_RelationshipType::fields();
      $this->_fields['relationship_type'] = $relationship_type_fields['label_a_b'];
      $this->_fields['relationship_type']['where'] = 'relationship_type.label_a_b';
      // Add custom fields
      $this->_fields = array_merge($this->_fields, CRM_Core_BAO_CustomField::getFieldsForImport('Relationship'));
    }

    // basically do all the work once, and then reuse it
    $this->initialize();
  }

  /**
   * Function which actually does all the work for the constructor.
   *
   * @return void
   */
  public function initialize() {
    $this->_select = array();
    $this->_element = array();
    $this->_tables = array();
    $this->_whereTables = array();
    $this->_where = array();
    $this->_options = array();
    $this->_cfIDs = array();
    $this->_paramLookup = array();
    $this->_having = array();

    $this->_customQuery = NULL;

    $this->_select['relationship_id'] = 'relationship.id as relationship_id';
    $this->_element['relationship_id'] = 1;
    $this->_tables['civicrm_relationship'] = 1;
    $this->_tables['civicrm_relationship_type'] = 1;
    $this->_tables['civicrm_contact'] = 1;

    if (!empty($this->_params)) {
      $this->buildParamsLookup();
    }

    $this->_whereTables = $this->_tables;

    $this->selectClause();
    $this->_whereClause = $this->whereClause();
    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL);
  }

  public function buildParamsLookup() {

    foreach ($this->_params as $value) {
      if (empty($value[0])) {
        continue;
      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID($value[0]);
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = array();
        }
        // Set wildcard value based on "and/or" selection
        foreach ($this->_params as $key => $param) {
          if ($param[0] == $value[0] . '_operator') {
            $value[4] = $param[2] == 'or';
            break;
          }
        }
        $this->_cfIDs[$cfID][] = $value;
      }

      if (!array_key_exists($value[0], $this->_paramLookup)) {
        $this->_paramLookup[$value[0]] = array();
      }
      $this->_paramLookup[$value[0]][] = $value;
    }
  }

  /**
   * Where clause for including contact ids
   *
   * @return void
   */
  public function includeRelationshipIDs() {
    if (!$this->_includeRelationshipIds || empty($this->_params)) {
      return;
    }

    $relationshipIds = array();
    foreach ($this->_params as $id => $values) {
      if (substr($values[0], 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
        $relationshipIds[] = substr($values[0], CRM_Core_Form::CB_PREFIX_LEN);
      }
    }
    if (!empty($relationshipIds)) {
      $this->_where[0][] = " ( relationship.id IN (" . implode(',', $relationshipIds) . " ) ) ";
    }
  }

  /**
   * Given a list of conditions in params generate the required.
   * where clause
   *
   * @return string
   */
  public function whereClause() {
    $this->_where[0] = array();

    $this->includeRelationshipIds();
    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (empty($this->_params[$id][0])) {
          continue;
        }
        $this->whereClauseSingle($this->_params[$id]);
      }
    }

    if ($this->_customQuery) {
      if (!empty($this->_customQuery->_where)) {
        $this->_where = CRM_Utils_Array::crmArrayMerge($this->_where, $this->_customQuery->_where);
      }
    }

    $clauses = array();
    $andClauses = array();

    $validClauses = 0;
    if (!empty($this->_where)) {
      foreach ($this->_where as $grouping => $values) {
        if ($grouping > 0 && !empty($values)) {
          $clauses[$grouping] = ' ( ' . implode(" {$this->_operator} ", $values) . ' ) ';
          $validClauses++;
        }
      }

      if (!empty($this->_where[0])) {
        $andClauses[] = ' ( ' . implode(" {$this->_operator} ", $this->_where[0]) . ' ) ';
      }
      if (!empty($clauses)) {
        $andClauses[] = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }

      if ($validClauses > 1) {
        $this->_useDistinct = TRUE;
      }
    }
    return implode(' AND ', $andClauses);
  }

  /**
   * @param $values
   */
  public function whereClauseSingle(&$values) {
    // do not process custom fields
    if (CRM_Core_BAO_CustomField::getKeyID($values[0])) {
      return;
    }
    list($name, $op, $value, $grouping, $wildcard) = $values;
    switch ($name) {
      case 'is_active':
        $today = date('Ymd');
        if ($value == 0) {
          $this->_where[$grouping][] = "(
relationship.is_active = 1 AND
( relationship.end_date IS NULL OR relationship.end_date >= {$today} ) AND
( relationship.start_date IS NULL OR relationship.start_date <= {$today} )
)";
        }
        elseif ($value == 1) {
          $this->_where[$grouping][] = "(
relationship.is_active = 0 OR
relationship.end_date < {$today} OR
relationship.start_date > {$today}
)";
        }
        return;

      case 'relationship_type_id':
        $relationship_type_ids = array();
        foreach ($value as $key => $relationship_type_value) {
          // we gebruiken de key om relaties in 2 richtingen op te vangen.
          $relationship_type_ids[substr_replace($relationship_type_value, "", -4)] = substr_replace($relationship_type_value, "", -4);
        }
        $relationship_type_ids_string = implode("', '", $relationship_type_ids);
        $this->_where[$grouping][] = "relationship.relationship_type_id in (' $relationship_type_ids_string  ')";
        return;

      case 'target_name':

        $target_name = trim($value);
        if (substr($target_name, 0, 1) == '"' &&
            substr($target_name, -1, 1) == '"'
        ) {
          $target_name = substr($target_name, 1, -1);
          $target_name = strtolower(CRM_Core_DAO::escapeString($target_name));
          $this->_where[$grouping][] = "(contact_a.display_name = '$target_name' or contact_b.display_name = '$target_name')";
        }
        else {
          $target_name = strtolower(CRM_Core_DAO::escapeString($target_name));
          $this->_where[$grouping][] = "(contact_a.display_name LIKE '%{$target_name}%' or contact_b.display_name LIKE '%{$target_name}%')";
        }

        return;

      default:
        //Doe niets bij niet ondersteunde parameter. 
        return;
    }
  }

  /**
   * @param $formValues
   * @param int $wildcard
   * @param bool $useEquals
   *
   * @param string $apiEntity
   *
   * @return array
   */
  // NAKIJKEN
  public static function convertFormValues(&$formValues, $wildcard = 0, $useEquals = FALSE, $apiEntity = NULL) {
    $params = array();
    if (empty($formValues)) {
      return $params;
    }
    foreach ($formValues as $id => $values) {
      if ($id == 'start_date_relative' ||
          $id == 'end_date_relative'
      ) {
        if ($id == 'start_date_relative') {
          $fromRange = 'start_date_low';
          $toRange = 'start_date_high';
        }
        elseif ($id == 'end_date_relative') {
          $fromRange = 'relationship_end_date_low';
          $toRange = 'end_date_high';
        }
        
        if (array_key_exists($fromRange, $formValues) && array_key_exists($toRange, $formValues)) {
          CRM_Contact_BAO_Query::fixDateValues($formValues[$id], $formValues[$fromRange], $formValues[$toRange]);
          continue;
        }
      }
      else {
        $values = CRM_Relationship_BAO_Query::fixWhereValues($id, $values, $wildcard, $useEquals, $apiEntity);

        if (!$values) {
          continue;
        }
        $params[] = $values;
      }
    }
    return $params;
  }

  /**
   * @param int $id
   * @param $values
   * @param int $wildcard
   * @param bool $useEquals
   *
   * @param string $apiEntity
   *
   * @return array|null
   */
  public static function &fixWhereValues($id, &$values, $wildcard = 0, $useEquals = FALSE, $apiEntity = NULL) {
    // skip a few search variables
    static $skipWhere = NULL;
    static $likeNames = NULL;
    $result = NULL;
    // Change camelCase EntityName to lowercase with underscores
    $apiEntity = _civicrm_api_get_entity_name_from_camel($apiEntity);

    if (CRM_Utils_System::isNull($values)) {
      return $result;
    }

    if (!$skipWhere) {
      $skipWhere = array(
        'task',
        'radio_ts',
        'uf_group_id',
        'component_mode',
        'qfKey',
        'operator',
      );
    }

    if (in_array($id, $skipWhere) ||
        substr($id, 0, 4) == '_qf_' ||
        substr($id, 0, 7) == 'hidden_'
    ) {
      return $result;
    }

    if ($apiEntity &&
        (substr($id, 0, strlen($apiEntity)) != $apiEntity)
    ) {
      $id = $apiEntity . '_' . $id;
    }

    if (!$likeNames) {
      $likeNames = array('relationship_target_name');
    }

    if (!$useEquals && in_array($id, $likeNames)) {
      $result = array($id, 'LIKE', $values, 0, 1);
    }
    elseif (is_string($values) && strpos($values, '%') !== FALSE) {
      $result = array($id, 'LIKE', $values, 0, 0);
    }
    elseif ($id == 'relationship_type_id' ||
        (!empty($values) && is_array($values) && !in_array(key($values), CRM_Core_DAO::acceptedSQLOperators(), TRUE))
    ) {
      $result = array($id, 'IN', $values, 0, $wildcard);
    }
    else {
      $result = array($id, '=', $values, 0, $wildcard);
    }
    return $result;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
  $includeCustomFields = TRUE
  ) {
    $properties = array(
      'contact_id_a' => 1,
      'contact_id_b' => 1,
      'contact_a' => 1,
      'contact_b' => 1,
      'relationship_type_id' => 1,
      'relationship_type' => 1,
      'start_date' => 1,
      'end_date' => 1,
      'is_active' => 1,
    );

    if ($includeCustomFields) {
      // also get all the custom membership properties
      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Relationship');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }
    return $properties;
  }

  /**
   * Add all the elements of relation search.
   *
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildSearchForm(&$form) {

    // text for sort_name
    $form->addElement('text', 'target_name', ts('Target Contact'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'
        )
    );

    $allRelationshipType = array();
    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    $form->add('select', 'relationship_type_id', ts('Relationship Type'), array('' => ts('- select -')) + $allRelationshipType, FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple',));

    // relation status
    $relStatusOption = array(ts('Active'), ts('Inactive'), ts('All'));
    $form->addRadio('is_active', ts('Relationship Status'), $relStatusOption);
    $form->setDefaults(array('is_active' => 0));

    CRM_Core_Form_Date::buildDateRange($form, 'start_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'end_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    // add all the custom  searchable fields
    $relationship = array('Relationship');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $relationship);
    if ($groupDetails) {
      $form->assign('relationshipGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, FALSE, TRUE
          );
        }
      }
    }
  }

  /**
   * Create and query the db for a relationship search.
   *
   * @param int $offset
   *   The offset for the query.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The order by string.
   * @param bool $count
   *   Is this a count only query ?.
   * @param bool $includeRelationshipIds
   *   Should we include relationship ids?.
   * @param bool $groupRelationships
   *   If true, return only the relationship ids.
   * @param bool $returnQuery
   *   Should we return the query as a string.
   * @param string $additionalWhereClause
   *   If the caller wants to further restrict the search (used for components).
   * @param null $sortOrder
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   *
   * @param bool $skipOrderAndLimit
   *
   * @return CRM_Core_DAO
   */
  public function searchQuery(
  $offset = 0, $rowCount = 0, $sort = NULL, $count = FALSE, $includeRelationshipIds = FALSE, $groupRelationships = FALSE, $returnQuery = FALSE, $additionalWhereClause = NULL, $sortOrder = NULL, $additionalFromClause = NULL, $skipOrderAndLimit = FALSE
  ) {

    if ($includeRelationshipIds) {
      $this->_includeRelationshipIds = TRUE;
      $this->_whereClause = $this->whereClause();
    }

    // building the query string
    $groupBy = NULL;
    if (!$count) {
      if (isset($this->_groupByComponentClause)) {
        $groupBy = $this->_groupByComponentClause;
      }
      elseif ($this->_useGroupBy) {
        $groupBy = ' GROUP BY relationship.id';
      }
    }

    $order = $orderBy = $limit = '';
    if (!$count) {
      $config = CRM_Core_Config::singleton();
      if ($config->includeOrderByClause ||
          isset($this->_distinctComponentClause)
      ) {
        if ($sort) {
          if (is_string($sort)) {
            $orderBy = $sort;
          }
          else {
            $orderBy = trim($sort->orderBy());
          }
          if (!empty($orderBy)) {
            $orderBy = CRM_Utils_Type::escape($orderBy, 'String');
            $order = " ORDER BY $orderBy";

            if ($sortOrder) {
              $sortOrder = CRM_Utils_Type::escape($sortOrder, 'String');
              $order .= " $sortOrder";
            }

            // always add relationship.id to the ORDER clause
            // so the order is deterministic
            if (strpos('relationship.id', $order) === FALSE) {
              $order .= ", relationship.id";
            }
          }
        }
        else {
          $order = " ORDER BY relationship.id";
        }
      }

      // hack for order clause
      if ($order) {
        $fieldStr = trim(str_replace('ORDER BY', '', $order));
        $fieldOrder = explode(' ', $fieldStr);
        $field = $fieldOrder[0];

        if ($field) {         
          $this->_fromClause = self::fromClause($this->_tables, NULL, NULL);
          $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL);
        }
      }

      if ($rowCount > 0 && $offset >= 0) {
        $offset = CRM_Utils_Type::escape($offset, 'Int');
        $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');
        $limit = " LIMIT $offset, $rowCount ";
      }
    }

    // CRM-15231
    $this->_sort = $sort;

    list($select, $from, $where, $having) = $this->query($count, $groupRelationships);

    if ($additionalWhereClause) {
      $where = $where . ' AND ' . $additionalWhereClause;
    }

    //additional from clause should be w/ proper joins.
    if ($additionalFromClause) {
      $from .= "\n" . $additionalFromClause;
    }

    if ($skipOrderAndLimit) {
      $query = "$select $from $where $having $groupBy";
    }
    else {
      $query = "$select $from $where $having $groupBy $order $limit";
    }
    if ($returnQuery) {
      return $query;
    }
    if ($count) {
      return CRM_Core_DAO::singleValueQuery($query);
    }

    $dao = CRM_Core_DAO::executeQuery($query);
    if ($groupRelationships) {
      $ids = array();
      while ($dao->fetch()) {
        $ids[] = $dao->id;
      }
      return implode(',', $ids);
    }

    return $dao;
  }

  /**
   * Given a list of conditions in params and a list of desired
   * return Properties generate the required select and from
   * clauses. Note that since the where clause introduces new
   * tables, the initial attempt also retrieves all variables used
   * in the params list
   *
   * @return void
   */
  public function selectClause() {
    foreach ($this->_fields as $name => $field) {
      // if this is a hierarchical name, we ignore it
      $names = explode('-', $name);
      if (count($names) > 1 && isset($names[1]) && is_numeric($names[1])) {
        continue;
      }

      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);

      if (!empty($this->_returnProperties[$name])) {
        if ($cfID) {
          // add to cfIDs array if not present
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
        }

        elseif (isset($field['where'])) {
          list($tableName, $fieldName) = explode('.', $field['where'], 2);
          if (isset($tableName)) {
            // also get the id of the tableName
            $tName = substr($tableName, 8);

            if ($tName == 'relationship') {
              if ($fieldName != 'id') {
                $this->_select[$name] = "relationship.{$fieldName}  as `$name`";
              }
            }
            else {
              $this->_select[$name] = "{$field['where']} as `$name`";
            }
          }
        }
      }
    }

    if (!empty($this->_cfIDs)) {
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs);
      $this->_customQuery->query();
      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      // hack, replace table name
      foreach ($this->_customQuery->_tables as $tableKey => $tableValue) {
        $this->_customQuery->_tables[$tableKey] = str_replace("`civicrm_relationship`", "relationship", $this->_customQuery->_tables[$tableKey]);
      }
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
      // hack, replace table name
      foreach ($this->_customQuery->_whereTables as $tableKey => $tableValue) {
        $this->_customQuery->_whereTables[$tableKey] = str_replace("`civicrm_relationship`", "relationship", $this->_customQuery->_whereTables[$tableKey]);
      }
      $this->_whereTables = array_merge($this->_whereTables, $this->_customQuery->_whereTables);
      $this->_options = $this->_customQuery->_options;
    }
  }

  /**
   * Generate the query based on what type of query we need.
   *
   * @param bool $count
   * @param bool $groupRelationships
   *
   * @return array
   *   sql query parts as an array
   */
  public function query($count = FALSE, $groupRelationships = FALSE) {
    if ($count) {
      if (isset($this->_rowCountClause)) {
        $select = "SELECT {$this->_rowCountClause}";
      }
      elseif (isset($this->_distinctComponentClause)) {
        // we add distinct to get the right count for components
        // for the more complex result set, we use GROUP BY the same id
        // CRM-9630
        $select = "SELECT count( DISTINCT {$this->_distinctComponentClause} )";
      }
      else {
        $select = 'SELECT count(DISTINCT relationship.id) as rowCount';
      }
      $from = $this->_simpleFromClause;
      if ($this->_useDistinct) {
        $this->_useGroupBy = TRUE;
      }
    }
    elseif ($groupRelationships) {
      $select = 'SELECT relationship.id as id';
      if ($this->_useDistinct) {
        $this->_useGroupBy = TRUE;
      }
      $from = $this->_simpleFromClause;
    }
    else {
      $select = "SELECT ";
      if (isset($this->_distinctComponentClause)) {
        $select .= "{$this->_distinctComponentClause}, ";
      }
      $select .= implode(', ', $this->_select);
      $from = $this->_fromClause;
    }

    $where = '';
    if (!empty($this->_whereClause)) {
      $where = "WHERE {$this->_whereClause}";
    }

    if (!empty($this->_permissionWhereClause) && empty($this->_displayRelationshipType)) {
      if (empty($where)) {
        $where = "WHERE $this->_permissionWhereClause";
      }
      else {
        $where = "$where AND $this->_permissionWhereClause";
      }
    }

    $having = '';
    if (!empty($this->_having)) {
      foreach ($this->_having as $havingSets) {
        foreach ($havingSets as $havingSet) {
          $havingValue[] = $havingSet;
        }
      }
      $having = ' HAVING ' . implode(' AND ', $havingValue);
    }

    return array($select, $from, $where, $having);
  }

  /**
   * Create the from clause.
   *
   * @param array $tables
   *   Tables that need to be included in this from clause.
   *                      if null, return mimimal from clause (i.e. civicrm_relationship)
   * @param array $inner
   *   Tables that should be inner-joined.
   * @param array $right
   *   Tables that should be right-joined.
   *
   * @return string
   *   the from clause
   */
  public static function fromClause(&$tables, $inner = NULL, $right = NULL) {

    $from = ' FROM civicrm_relationship relationship';
    if (empty($tables)) {
      return $from;
    }

    //format the table list according to the weight
    $info = CRM_Core_TableHierarchy::info();
    foreach ($tables as $key => $value) {
      $k = 99;
      if (strpos($key, '-') !== FALSE) {
        $keyArray = explode('-', $key);
        $k = CRM_Utils_Array::value('civicrm_' . $keyArray[1], $info, 99);
      }
      elseif (strpos($key, '_') !== FALSE) {
        $keyArray = explode('_', $key);
        if (is_numeric(array_pop($keyArray))) {
          $k = CRM_Utils_Array::value(implode('_', $keyArray), $info, 99);
        }
        else {
          $k = CRM_Utils_Array::value($key, $info, 99);
        }
      }
      else {
        $k = CRM_Utils_Array::value($key, $info, 99);
      }
      $tempTable[$k . ".$key"] = $key;
    }
    ksort($tempTable);
    $newTables = array();
    foreach ($tempTable as $key) {
      $newTables[$key] = $tables[$key];
    }

    $tables = $newTables;

    foreach ($tables as $name => $value) {
      if (!$value) {
        continue;
      }

      if (!empty($inner[$name])) {
        $side = 'INNER';
      }
      elseif (!empty($right[$name])) {
        $side = 'RIGHT';
      }
      else {
        $side = 'LEFT';
      }

      if ($value != 1) {
        // if there is already a join statement in value, use value itself
        if (strpos($value, 'JOIN')) {
          $from .= " $value ";
        }
        else {
          $from .= " $side JOIN $name ON ( $value ) ";
        }
        continue;
      }
      switch ($name) {

        case 'civicrm_relationship_type':
          $from .= " $side JOIN civicrm_relationship_type relationship_type ON relationship.relationship_type_id = relationship_type.id ";
          continue;

        case 'civicrm_contact':
          $from .= " $side JOIN civicrm_contact contact_a ON relationship.contact_id_a = contact_a.id ";
          $from .= " $side JOIN civicrm_contact contact_b ON relationship.contact_id_b = contact_b.id ";
          continue;
      }
    }
    return $from;
  }

}