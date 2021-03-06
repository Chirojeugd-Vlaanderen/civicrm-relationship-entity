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
   * The various search modes.
   *
   * @var int
   */
  const
      MODE_RELATIONSHIPS = 1;

  /**
   * The default set of return properties.
   *
   * @var array
   */
  static $_defaultReturnProperties = NULL;

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
   * The english language version of the query
   *
   * @var array
   */
  public $_qill;

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
   * Track open panes.
   *
   * @var array
   */
  public static $_openedPanes = array();

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
   * @param bool $includeRelationshipIds
   * @param bool $strict
   *
   * @param bool $skipPermission
   * @param string $operator
   *
   * @return \CRM_Relationship_BAO_Query
   */
  public function __construct(
  $params = NULL, $returnProperties = NULL, $fields = NULL, $includeRelationshipIds = FALSE, $strict = FALSE, $mode = 1, $skipPermission = FALSE, $operator = 'AND'
  ) {
    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = array();
    }

    if (empty($returnProperties)) {
      $this->_returnProperties = self::defaultReturnProperties($mode);
    }
    else {
      $this->_returnProperties = &$returnProperties;
    }

    $this->_includeRelationshipIds = $includeRelationshipIds;
    $this->_strict = $strict;
    $this->_mode = $mode;
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
    $this->_tables['contact_a'] = 1;
    $this->_tables['contact_b'] = 1;

    if (!empty($this->_params)) {
      $this->buildParamsLookup();
    }

    $this->_whereTables = $this->_tables;

    $this->selectClause();
    $this->_whereClause = $this->whereClause();
    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL);

    $this->openedSearchPanes(TRUE);
  }

  /**
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return $this->_qill;
  }

  /**
   * @param bool $reset
   *
   * @return array
   */
  public function openedSearchPanes($reset = FALSE) {
    if (!$reset || empty($this->_whereTables)) {
      return self::$_openedPanes;
    }

    // pane name to table mapper
    $panesMapper = array(
      ts('Contact A') => 'contact_a',
      ts('Contact B') => 'contact_b',
    );

    foreach (array_keys($this->_whereTables) as $table) {
      if ($panName = array_search($table, $panesMapper)) {
        self::$_openedPanes[$panName] = TRUE;
      }
    }

    return self::$_openedPanes;
  }

  /**
   * Fetch a list of contacts from the prev/next cache for displaying a search results page
   *
   * @param string $cacheKey
   * @param int $offset
   * @param int $rowCount
   * @param bool $includeRelationshipIds
   * @return CRM_Core_DAO
   */
  public function getCachedRelationships($cacheKey, $offset, $rowCount, $includeRelationshipIds) {
    $this->_includeRelationshipIds = $includeRelationshipIds;
    list($select, $from, $where) = $this->query(FALSE, FALSE);
    // strip FROM civicrm_relationship relationship van from-clause 39 tekens dus.
    $from = " FROM civicrm_prevnext_cache pnc INNER JOIN civicrm_relationship relationship ON relationship.id = pnc.entity_id1 AND pnc.cacheKey = '$cacheKey' " . substr($from, 39);
    $order = " ORDER BY pnc.id";
    $groupBy = " GROUP BY relationship.id";
    $limit = " LIMIT $offset, $rowCount";
    $query = "$select $from $where $groupBy $order $limit";

    return CRM_Core_DAO::executeQuery($query);
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
    $this->_qill[0] = array();

    $this->includeRelationshipIds();
    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (empty($this->_params[$id][0])) {
          continue;
        }
        // check for both id and contact_id
        if ($this->_params[$id][0] == 'id' || $this->_params[$id][0] == 'relationship_id') {
          $this->_where[0][] = self::buildClause("relationship.id", $this->_params[$id][1], $this->_params[$id][2]);
        }
        else {
          $this->whereClauseSingle($this->_params[$id]);
        }
      }
    }

    if ($this->_customQuery) {
      if (!empty($this->_customQuery->_where)) {
        $this->_where = CRM_Utils_Array::crmArrayMerge($this->_where, $this->_customQuery->_where);
      }
      $this->_qill = CRM_Utils_Array::crmArrayMerge($this->_qill, $this->_customQuery->_qill);
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
      case 'relationship_type_id':
        $this->relationshipType($values);
        return;

      case 'is_active':
        $this->isActive($values);
        return;

      case 'start_date_low':
      case 'start_date_high':
      case 'end_date_low':
      case 'end_date_high':
        $this->relationshipDate($values);
        return;

      case 'target_name':
        $this->targetName($values);
        return;

      case 'contact_a_display_name':
      case 'contact_b_display_name':
        $this->contactDisplayName($values);
        return;

      case 'contact_a_email':
      case 'contact_b_email':
        $this->email($values);
        return;

      case 'contact_a_contact_type':
      case 'contact_b_contact_type':
        $this->contactType($values);
        return;

      case 'contact_a_phone_numeric':
      case 'contact_b_phone_numeric':
        $this->phone_numeric($values);
        return;

      case 'contact_a_phone_phone_type_id':
      case 'contact_a_phone_location_type_id':
        $this->phone_option_group($values, 'contact_a');
        return;

      case 'contact_b_phone_phone_type_id':
      case 'contact_b_phone_location_type_id':
        $this->phone_option_group($values, 'contact_b');
        return;

      case 'contact_a_street_address':
        $this->street_address($values, 'contact_a');
        return;

      case 'contact_b_street_address':
        $this->street_address($values, 'contact_b');
        return;

      case 'contact_a_city':
        $this->city($values, 'contact_a');
        return;

      case 'contact_b_city':
        $this->city($values, 'contact_b');
        return;

      case 'contact_a_location_type':
        $this->locationType($values, 'contact_a');
        return;

      case 'contact_b_location_type':
        $this->locationType($values, 'contact_b');
        return;

      case 'contact_a_postal_code':
      case 'contact_a_postal_code_low':
      case 'contact_a_postal_code_high':
        $this->postalCode($values, 'contact_a');
        return;

      case 'contact_b_postal_code':
      case 'contact_b_postal_code_low':
      case 'contact_b_postal_code_high':
        $this->postalCode($values, 'contact_b');
        return;

      case 'contact_a_birth_date_low':
      case 'contact_a_birth_date_high':
      case 'contact_a_deceased_date_low':
      case 'contact_a_deceased_date_high':
        $this->demographics($values, 'contact_a');
        return;

      case 'contact_b_birth_date_low':
      case 'contact_b_birth_date_high':
      case 'contact_b_deceased_date_low':
      case 'contact_b_deceased_date_high':
        $this->demographics($values, 'contact_b');
        return;

      case 'contact_a_contact_id':
        $this->contact_id($values, 'contact_a');
        return;

      case 'contact_b_contact_id':
        $this->contact_id($values, 'contact_b');
        return;

      case 'contact_a_external_identifier':
        $this->contact_external_id($values, 'contact_a');
        return;

      case 'contact_b_externla_identifier':
        $this->contact_external_id($values, 'contact_b');
        return;

      case 'contact_a_is_deceased':
        $this->deceased($values, 'contact_a');
        return;

      case 'contact_b_is_deceased':
        $this->deceased($values, 'contact_b');
        return;

      case 'contact_a_gender_id':
        $this->gender($values, 'contact_a');
        return;

      case 'contact_b_gender_id':
        $this->gender($values, 'contact_b');
        return;

      case 'contact_a_job_title':
        $this->job($values, 'contact_a');
        return;

      case 'contact_b_job_title':
        $this->job($values, 'contact_b');
        return;

      case 'entryURL':
        return;

      default:
        //dsm("TODO / IGNORE: where clause voor $name");
        return;
    }
  }

  /**
   * @param $values
   */
  public function gender(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("{$contact}.gender_id", $op, $value);
    $this->_qill[$grouping][] = "$name $op \"$value\"";
  }

  /**
   * @param $values
   */
  public function deceased(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("{$contact}.is_deceased", $op, $value);
    $this->_qill[$grouping][] = "$name $op \"$value\"";
  }

  /**
   * @param $values
   */
  public function contact_id(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("{$contact}.id", $op, $value);
    $this->_qill[$grouping][] = "$name $op \"$value\"";
  }

  /**
   * @param $values
   */
  public function contact_external_id(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("{$contact}.external_identifier", $op, $value);
    $this->_qill[$grouping][] = "$name $op \"$value\"";
  }

  /**
   * @param $values
   */
  public function demographics(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (($name == $contact . '_birth_date_low') || ($name == $contact . '_birth_date_high')) {

      $this->dateQueryBuilder($values, $contact, $contact . '_birth_date', 'birth_date', ts('Birth Date')
      );
    }
    elseif (($name == $contact . '_deceased_date_low') || ($name == $contact . '_deceased_date_high')) {

      $this->dateQueryBuilder($values, $contact, $contact . '_deceased_date', 'deceased_date', ts('Deceased Date')
      );
    }
  }

  /**
   * @param string $name
   * @param $grouping
   *
   * @return null
   */
  public function &getWhereValues($name, $grouping) {
    $result = NULL;
    foreach ($this->_params as $values) {
      if ($values[0] == $name && $values[3] == $grouping) {
        return $values;
      }
    }

    return $result;
  }

  /**
   * @param $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param $fieldTitle
   * @param bool $appendTimeStamp
   */
  public function dateQueryBuilder(
  &$values, $tableName, $fieldName, $dbFieldName, $fieldTitle, $appendTimeStamp = TRUE
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
        $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName]) || !$value) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = $secondDate = $secondDateFormat = NULL;

      if ($name == $fieldName . '_low') {
        $firstOP = '>=';
        $firstPhrase = ts('greater than or equal to');
        $firstDate = CRM_Utils_Date::processDate($value);

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '<=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];

          if ($appendTimeStamp && strlen($secondValue) == 10) {
            $secondValue .= ' 23:59:59';
          }
          $secondDate = CRM_Utils_Date::processDate($secondValue);
        }
      }
      elseif ($name == $fieldName . '_high') {
        $firstOP = '<=';
        $firstPhrase = ts('less than or equal to');

        if ($appendTimeStamp && strlen($value) == 10) {
          $value .= ' 23:59:59';
        }
        $firstDate = CRM_Utils_Date::processDate($value);

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = CRM_Utils_Date::processDate($secondValue);
        }
      }

      if (!$appendTimeStamp) {
        $firstDate = substr($firstDate, 0, 8);
      }
      $firstDateFormat = CRM_Utils_Date::customFormat($firstDate);

      if ($secondDate) {
        if (!$appendTimeStamp) {
          $secondDate = substr($secondDate, 0, 8);
        }
        $secondDateFormat = CRM_Utils_Date::customFormat($secondDate);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      if ($secondDate) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$dbFieldName} $secondOP '$secondDate' )
";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\" " . ts('AND') . " $secondPhrase \"$secondDateFormat\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP '$firstDate'";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\"";
      }
    }

    if ($name == $fieldName) {
      //In Get API, for operators other then '=' the $value is in array(op => value) format
      if (is_array($value) && !empty($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }

      $date = $format = NULL;
      if (strstr($op, 'IN')) {
        $format = array();
        foreach ($value as &$date) {
          $date = CRM_Utils_Date::processDate($date);
          if (!$appendTimeStamp) {
            $date = substr($date, 0, 8);
          }
          $format[] = CRM_Utils_Date::customFormat($date);
        }
        $date = "('" . implode("','", $value) . "')";
        $format = implode(', ', $format);
      }
      elseif ($value && (!strstr($op, 'NULL') && !strstr($op, 'EMPTY'))) {
        $date = CRM_Utils_Date::processDate($value);
        if (!$appendTimeStamp) {
          $date = substr($date, 0, 8);
        }
        $format = CRM_Utils_Date::customFormat($date);
        $date = "'$date'";
      }

      if ($date) {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op $date";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op";
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      $op = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
      $this->_qill[$grouping][] = "$fieldTitle $op $format";
    }
  }

  /**
   * Where / qill clause for postal code
   *
   * @param $values
   *
   * @return void
   */
  public function postalCode(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    // Handle numeric postal code range searches properly by casting the column as numeric
    if (is_numeric($value)) {
      $field = 'ROUND(' . $contact . '_address.postal_code)';
      $val = CRM_Utils_Type::escape($value, 'Integer');
    }
    else {
      $field = $contact . '_address.postal_code';
      // Per CRM-17060 we might be looking at an 'IN' syntax so don't case arrays to string.
      if (!is_array($value)) {
        $val = CRM_Utils_Type::escape($value, 'String');
      }
      else {
        // Do we need to escape values here? I would expect buildClause does.
        $val = $value;
      }
    }

    $this->_tables[$contact . '_address'] = $this->_whereTables[$contact . '_address'] = 1;

    if ($name == $contact . '_postal_code') {
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($field, $op, $val, 'String');
      $this->_qill[$grouping][] = ts('Postal code') . " {$op} {$value}";
    }
    elseif ($name == $contact . '_postal_code_low') {
      $this->_where[$grouping][] = " ( $field >= '$val' ) ";
      $this->_qill[$grouping][] = ts('Postal code greater than or equal to \'%1\'', array(1 => $value));
    }
    elseif ($name == $contact . '_postal_code_high') {
      $this->_where[$grouping][] = " ( $field <= '$val' ) ";
      $this->_qill[$grouping][] = ts('Postal code less than or equal to \'%1\'', array(1 => $value));
    }
  }

  /**
   * Where / qill clause for location type
   *
   * @param $values
   * @param null $status
   *
   * @return void
   */
  public function locationType(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (is_array($value)) {
      $this->_where[$grouping][] = $contact . '_address.location_type_id IN (' . implode(',', $value) . ')';
      $this->_tables[$contact . '_address'] = 1;
      $this->_whereTables[$contact . '_address'] = 1;

      $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $names = array();
      foreach ($value as $id) {
        $names[] = $locationType[$id];
      }

      $this->_qill[$grouping][] = ts('Location Type') . ' - ' . implode(' ' . ts('or') . ' ', $names);
    }
  }

  /**
   * Where / qill clause for street_address
   *
   * @param $values
   *
   * @return void
   */
  public function city(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $value = trim($value);
    if (substr($value, 0, 1) == '"' &&
        substr($value, -1, 1) == '"'
    ) {
      $op = '=';
      $value = substr($value, 1, -1);
    }
    else {
      $op = 'LIKE';
    }

    $value = '"' . strtolower(CRM_Core_DAO::escapeString(trim($value))) . '%"';

    $this->_where[$grouping][] = 'LOWER(' . $contact . '_address.city) ' . $op . ' ' . $value;
    $this->_qill[$grouping][] = ts('City') . " $op $value";

    $this->_tables[$contact . '_address'] = $this->_whereTables[$contact . '_address'] = 1;
  }

  /**
   * Where / qill clause for street_address
   *
   * @param $values
   *
   * @return void
   */
  public function street_address(&$values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $value = trim($value);
    if (substr($value, 0, 1) == '"' &&
        substr($value, -1, 1) == '"'
    ) {
      $op = '=';
      $value = substr($value, 1, -1);
    }
    else {
      $op = 'LIKE';
    }

    $value = '"' . strtolower(CRM_Core_DAO::escapeString(trim($value))) . '%"';

    $this->_where[$grouping][] = 'LOWER(' . $contact . '_address.street_address) ' . $op . ' ' . $value;
    $this->_qill[$grouping][] = ts('Street') . " $op $value";

    $this->_tables[$contact . '_address'] = $this->_whereTables[$contact . '_address'] = 1;
  }

  /**
   * Where / qill clause for phone type/location
   *
   * @param $values
   *
   * @return void
   */
  public function phone_option_group($values, $contact) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $option = ($name == $contact . '_phone_phone_type_id' ? 'phone_type_id' : 'location_type_id');
    $options = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', $option);
    $optionName = $options[$value];
    $this->_qill[$grouping][] = ts('Phone') . ' ' . ($name == $contact . 'phone_phone_type_id' ? ts('type') : ('location')) . " $op $optionName";
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($contact . '_phone.' . $option, $op, $value, 'Integer');
    $this->_tables[$contact . '_phone'] = $this->_whereTables[$contact . '_phone'] = 1;
  }

  /**
   * Where / qill clause for phone number
   *
   * @param $values
   *
   * @return void
   */
  public function phone_numeric(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    // Strip non-numeric characters; allow wildcards
    $number = preg_replace('/[^\d%]/', '', $value);
    if ($number) {
      if (strpos($number, '%') === FALSE) {
        $number = "%$number%";
      }

      $this->_qill[$grouping][] = ts('Phone number contains') . " $number";

      if ($name == 'contact_a_phone_numeric') {
        $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('contact_a_phone.phone_numeric', 'LIKE', "$number", 'String');
        $this->_tables['contact_a_phone'] = $this->_whereTables['contact_a_phone'] = 1;
      }
      elseif ($name == 'contact_b_phone_numeric') {
        $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('contact_b_phone.phone_numeric', 'LIKE', "$number", 'String');
        $this->_tables['contact_b_phone'] = $this->_whereTables['contact_b_phone'] = 1;
      }
    }
  }

  /**
   * Where / qill clause for email
   *
   * @param $values
   *
   * @return void
   */
  public function email(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $value = trim($value);
    if (substr($value, 0, 1) == '"' &&
        substr($value, -1, 1) == '"'
    ) {
      $op = '=';
      $value = substr($value, 1, -1);
    }
    else {
      $op = 'LIKE';
    }

    $value = '"' . strtolower(CRM_Core_DAO::escapeString(trim($value))) . '%"';

    $this->_qill[$grouping][] = ts('Email') . " $op '$value'";
    if ($name == 'contact_a_email') {
      $this->_where[$grouping][] = " ( contact_a_email.email $op $value )";

      $this->_tables['contact_a_email'] = $this->_whereTables['contact_a_email'] = 1;
    }
    elseif ($name == 'contact_b_email') {
      $this->_where[$grouping][] = " ( contact_b_email.email $op $value )";

      $this->_tables['contact_b_email'] = $this->_whereTables['contact_b_email'] = 1;
    }
  }

  /**
   * Where / qill clause for contact_type
   *
   * @param $values
   *
   * @return void
   */
  public function contactType(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $subTypes = array();
    $clause = array();

    // account for search builder mapping multiple values
    if (!is_array($value)) {
      $values = self::parseSearchBuilderString($value, 'String');
      if (is_array($values)) {
        $value = array_flip($values);
      }
    }

    if (is_array($value)) {
      foreach ($value as $k => $v) {
        $subType = NULL;
        $contactType = $v;
        if (strpos($v, CRM_Core_DAO::VALUE_SEPARATOR)) {
          list($contactType, $subType) = explode(CRM_Core_DAO::VALUE_SEPARATOR, $v, 2);
        }
        if (!empty($subType)) {
          $subTypes[$subType] = 1;
        }
        $clause[$contactType] = "'" . CRM_Utils_Type::escape($contactType, 'String') . "'";
      }
    }
    else {
      $contactTypeANDSubType = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value, 2);
      $contactType = $contactTypeANDSubType[0];
      $subType = CRM_Utils_Array::value(1, $contactTypeANDSubType);
      if (!empty($subType)) {
        $subTypes[$subType] = 1;
      }
      $clause[$contactType] = "'" . CRM_Utils_Type::escape($contactType, 'String') . "'";
    }

    // fix for CRM-771
    if (!empty($clause)) {
      $quill = $clause;

      if ($name == 'contact_a_contact_type') {
        $this->_where[$grouping][] = "contact_a.contact_type IN (" . implode(',', $clause) . ')';
        $this->includeContactSubTypes($subTypes, $grouping, 'LIKE', 'contact_a');
      }
      elseif ($name == 'contact_b_contact_type') {
        $this->_where[$grouping][] = "contact_b.contact_type IN (" . implode(',', $clause) . ')';
        if (!empty($subTypes)) {
          $this->includeContactSubTypes($subTypes, $grouping, 'LIKE', 'contact_b');
        }
      }

      $this->_qill[$grouping][] = ts('Contact Type') . " IN " . implode(' ' . ts('or') . ' ', $quill);

      
    }
  }

  /**
   * @param $value
   * @param $grouping
   * @param string $op
   */
  public function includeContactSubTypes($value, $grouping, $op = 'LIKE', $contact) {

    $clause = array();
    $alias = "$contact.contact_sub_type";
    $qillOperators = array('NOT LIKE' => ts('Not Like')) + CRM_Core_SelectValues::getSearchBuilderOperators();

    $op = str_replace('IN', 'LIKE', $op);
    $op = str_replace('=', 'LIKE', $op);
    $op = str_replace('!', 'NOT ', $op);

    if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($alias, $op, $value, 'String');
    }
    elseif (is_array($value)) {
      foreach ($value as $k => $v) {
        if (!empty($k)) {
          $clause[$k] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($k, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
        }
      }
    }
    else {
      $clause[$value] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($value, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
    }

    if (!empty($clause)) {
      $this->_where[$grouping][] = "( " . implode(' OR ', $clause) . " )";
    }
    $this->_qill[$grouping][] = ts('Contact Subtype %1 ', array(1 => $qillOperators[$op])) . implode(' ' . ts('or') . ' ', array_keys($clause));
  }

  /**
   * Where / qill clause for contact sort_name
   *
   * @param $values
   *
   * @return void
   */
  public function job(&$values, $contact) {

    list($fieldName, $op, $value, $grouping, $wildcard) = $values;

    $value = trim($value);
    if (substr($value, 0, 1) == '"' &&
        substr($value, -1, 1) == '"'
    ) {
      $op = '=';
      $value = substr($value, 1, -1);
    }
    else {
      $op = 'LIKE';
    }

    $value = strtolower(CRM_Core_DAO::escapeString(trim($value))) . '%';

    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($contact . '.job_title', $op, "$value", 'String');
    $this->_qill[$grouping][] = ts('Job title is ') . $value;
  }

  /**
   * Where / qill clause for contact sort_name
   *
   * @param $values
   *
   * @return void
   */
  public function contactDisplayName(&$values) {

    list($fieldName, $op, $value, $grouping, $wildcard) = $values;

    $value = trim($value);
    if (substr($value, 0, 1) == '"' &&
        substr($value, -1, 1) == '"'
    ) {
      $op = '=';
      $value = substr($value, 1, -1);
    }
    else {
      $op = 'LIKE';
    }

    $value = '"' . strtolower(CRM_Core_DAO::escapeString(trim($value))) . '%"';

    if ($fieldName == 'contact_a_display_name') {
      $wc = "contact_a.display_name";
    }
    elseif ($fieldName == 'contact_b_display_name') {
      $wc = "contact_b.display_name";
    }

    $this->_where[$grouping][] = " ( $wc $op $value )";
    $this->_qill[$grouping][] = ts('Name') . " $op - '$value'";
  }

  /**
   * Where / qill clause for start & end date criteria of relationship
   * @param string $grouping
   * @param array $where
   *   = array to add where clauses to, in case you are generating a temp table.
   * not the main query.
   */
  public function relationshipDate(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    //start dates
    if (substr($name, 0, strlen('start')) === 'start') {
      if (substr($name, -strlen('low')) === 'low') {
        $this->_where[$grouping][] = "(relationship.start_date >= date({$value}))";
        $this->_qill[$grouping][] = ts('Relationship Start Date On or After ') . CRM_Utils_Date::customFormat($value);
      }
      elseif (substr($name, -strlen('high')) === 'high') {
        $this->_where[$grouping][] = "(relationship.start_date <=  date({$value}))";
        $this->_qill[$grouping][] = ts('Relationship Start Date Before or On ') . CRM_Utils_Date::customFormat($value);
      }
    }
    if (substr($name, 0, strlen('end')) === 'end') {
      if (substr($name, -strlen('low')) === 'low') {
        $this->_where[$grouping][] = "(relationship.end_date >=  date({$value}))";
        $this->_qill[$grouping][] = ts('Relationship End Date On or After ') . CRM_Utils_Date::customFormat($value);
      }
      elseif (substr($name, -strlen('high')) === 'high') {
        $this->_where[$grouping][] = "(relationship.end_date <=  date({$value}))";
        $this->_qill[$grouping][] = ts('Relationship End Date Before or On ') . CRM_Utils_Date::customFormat($value);
      }
    }
  }

  /**
   * Where / qill clause for is_active
   *
   * @param $values
   *
   * @return void
   */
  public function isActive(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $today = date('Ymd');
    if ($value == 0) {
      $this->_where[$grouping][] = "(
relationship.is_active = 1 AND
( relationship.end_date IS NULL OR relationship.end_date >= {$today} ) AND
( relationship.start_date IS NULL OR relationship.start_date <= {$today} )
)";
      $this->_qill[$grouping][] = ts('Relationship - Active and Current');
    }
    elseif ($value == 1) {
      $this->_where[$grouping][] = "(
relationship.is_active = 0 OR
relationship.end_date < {$today} OR
relationship.start_date > {$today}
)";
      $this->_qill[$grouping][] = ts('Relationship - Inactive or not Current');
    }
  }

  /**
   * Where / qill clause for target_name
   *
   * @param $values
   *
   * @return void
   */
  public function targetName(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $target_name = trim($value);
    if (substr($target_name, 0, 1) == '"' &&
        substr($target_name, -1, 1) == '"'
    ) {
      $op = '=';
      $target_name = substr($target_name, 1, -1);
         }
    else {
      $op = 'LIKE';
    }
    $target_name = strtolower(CRM_Core_DAO::escapeString($target_name));
    $this->_where[$grouping][] = "(contact_a.display_name ' $op $target_name' or contact_b.display_name ' $op $target_name')";

    $this->_qill[$grouping][] = ts('Contact name is ') . $value;
  }

  /**
   * Where / qill clause for relationship_type
   *
   * @param $values
   *
   * @return void
   */
  public function relationshipType(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $clause = array();

    foreach ($value as $k => $relationship_type_value) {
      // we gebruiken de key om relaties in 2 richtingen op te vangen.
      $relationship_type_id = substr_replace($relationship_type_value, "", -4);
      $relationship_type_params = array('id' => $relationship_type_id);
      CRM_Contact_BAO_RelationshipType::retrieve($relationship_type_params, $relationship_type);
      $clause[$relationship_type['label_a_b']] = $relationship_type_id;
    }

    $this->_where[$grouping][] = "relationship.relationship_type_id $op ('" . implode("', '", $clause) . "')";
    $this->_qill[$grouping][] = ts('Relationship Type') . " $op " . implode(' ' . ts('or') . ' ', array_keys($clause));
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
          $fromRange = 'end_date_low';
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
    elseif ($id == 'relationship_type_id') {
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
  public static function defaultReturnProperties($mode = 1) {
    if (!isset(self::$_defaultReturnProperties)) {
      self::$_defaultReturnProperties = array();
    }

    if (!isset(self::$_defaultReturnProperties[$mode])) {
      if (empty(self::$_defaultReturnProperties[$mode])) {
        self::$_defaultReturnProperties[$mode] = array(
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
      }
    }
    return self::$_defaultReturnProperties[$mode];
  }

  /**
   * Create and query the db for an contact search.
   *
   * @param int $offset
   *   The offset for the query.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The order by string.
   * @param bool $count
   *   Is this a count only query ?.
   * @param bool $includeContactIds
   *   Should we include contact ids?.
   * @param bool $sortByChar
   *   If true returns the distinct array of first characters for search results.
   * @param bool $groupContacts
   *   If true, return only the contact ids.
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
  $offset = 0, $rowCount = 0, $sort = NULL, $count = FALSE, $includeRelationshipIds = FALSE, $sortByChar = FALSE, $groupRelationships = FALSE, $returnQuery = FALSE, $additionalWhereClause = NULL, $sortOrder = NULL, $additionalFromClause = NULL, $skipOrderAndLimit = FALSE
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
        elseif ($sortByChar) {
          $order = " ORDER BY UPPER(LEFT(contact_a.sort_name, 1)) asc";
        }
        else {
          $order = " ORDER BY contact_a.sort_name asc, relationship.id";
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
    //dsm($query);
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

      if (!empty($this->_paramLookup[$name]) || !empty($this->_returnProperties[$name])) {
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
            $this->_select[$name] = "$tableName.{$fieldName}  as `$name`";
          }
        }
        else {
          //dsm('volgende velden worden niet in select verwerkt');
          //dsm($name);
        }

        if ($cfID && !empty($field['is_search_range'])) {
          // this is a custom field with range search enabled, so we better check for two/from values
          if (!empty($this->_paramLookup[$name . '_from'])) {
            if (!array_key_exists($cfID, $this->_cfIDs)) {
              $this->_cfIDs[$cfID] = array();
            }
            foreach ($this->_paramLookup[$name . '_from'] as $pID => $p) {
              // search in the cdID array for the same grouping
              $fnd = FALSE;
              foreach ($this->_cfIDs[$cfID] as $cID => $c) {
                if ($c[3] == $p[3]) {
                  $this->_cfIDs[$cfID][$cID][2]['from'] = $p[2];
                  $fnd = TRUE;
                }
              }
              if (!$fnd) {
                $p[2] = array('from' => $p[2]);
                $this->_cfIDs[$cfID][] = $p;
              }
            }
          }
          if (!empty($this->_paramLookup[$name . '_to'])) {
            if (!array_key_exists($cfID, $this->_cfIDs)) {
              $this->_cfIDs[$cfID] = array();
            }
            foreach ($this->_paramLookup[$name . '_to'] as $pID => $p) {
              // search in the cdID array for the same grouping
              $fnd = FALSE;
              foreach ($this->_cfIDs[$cfID] as $cID => $c) {
                if ($c[4] == $p[4]) {
                  $this->_cfIDs[$cfID][$cID][2]['to'] = $p[2];
                  $fnd = TRUE;
                }
              }
              if (!$fnd) {
                $p[2] = array('to' => $p[2]);
                $this->_cfIDs[$cfID][] = $p;
              }
            }
          }
        }
      }
    }
    if (!empty($this->_cfIDs)) {
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs);
      $this->_customQuery->query();
      // Customquery hardcodes te relationship table to civicrm_relationship
      // We call this relationship, so we need to change this.
      $this->_customQuery->_tables = str_replace('civicrm_relationship', 'relationship', $this->_customQuery->_tables);
      $this->_customQuery->_whereTables = str_replace('civicrm_relationship', 'relationship', $this->_customQuery->_whereTables);

      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
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

    // to handle table dependencies of components
    CRM_Core_Component::tableNames($tables);

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

        case 'civicrm_relationship':
          continue;

        case 'civicrm_relationship_type':
          $from .= " $side JOIN civicrm_relationship_type relationship_type ON relationship.relationship_type_id = relationship_type.id ";
          continue;

        case 'contact_a':
          $from .= " $side JOIN civicrm_contact contact_a ON relationship.contact_id_a = contact_a.id ";
          continue;

        case 'contact_b':
          $from .= " $side JOIN civicrm_contact contact_b ON relationship.contact_id_b = contact_b.id ";
          continue;

        case 'contact_a_email':
          $from .= " $side JOIN civicrm_email contact_a_email ON (contact_a.id = contact_a_email.contact_id AND contact_a_email.is_primary = 1) ";
          continue;

        case 'contact_b_email':
          $from .= " $side JOIN civicrm_email contact_b_email ON (contact_b.id = contact_b_email.contact_id AND contact_b_email.is_primary = 1) ";
          continue;

        case 'contact_a_phone':
          $from .= " $side JOIN civicrm_phone contact_a_phone ON (contact_a.id = contact_a_phone.contact_id AND contact_a_phone.is_primary = 1) ";
          continue;

        case 'contact_b_phone':
          $from .= " $side JOIN civicrm_phone contact_b_phone ON (contact_b.id = contact_b_phone.contact_id AND contact_b_phone.is_primary = 1) ";
          continue;

        case 'contact_a_address':
          $from .= " $side JOIN civicrm_address contact_a_address ON (contact_a.id = contact_a_address.contact_id AND contact_a_address.is_primary = 1) ";
          continue;

        case 'contact_b_address':
          $from .= " $side JOIN civicrm_address contact_b_address ON (contact_b.id = contact_b_address.contact_id AND contact_b_address.is_primary = 1) ";
          continue;

        default;
          //dsm("TODO: form clause moet nog geïmplementeerd worden voor $name");
          continue;
      }
    }
    return $from;
  }

}
