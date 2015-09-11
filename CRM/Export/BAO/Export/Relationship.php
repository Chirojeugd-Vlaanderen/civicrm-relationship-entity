<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CRM_Export_BAO_Export_Relationship extends CRM_Export_BAO_Export {

  /**
   * Get the list the export fields.
   *
   * @param int $selectAll
   *   User preference while export.
   * @param array $ids
   *   Contact ids.
   * @param array $params
   *   Associated array of fields.
   * @param string $order
   *   Order by clause.
   * @param array $fields
   *   Associated array of fields.
   * @param array $moreReturnProperties
   *   Additional return fields.
   * @param int $exportMode
   *   Export mode.
   * @param string $componentClause
   *   Component clause.
   * @param string $componentTable
   *   Component table.
   * @param bool $mergeSameAddress
   *   Merge records if they have same address.
   * @param bool $mergeSameHousehold
   *   Merge records if they belong to the same household.
   *
   * @param array $exportParams
   * @param string $queryOperator
   *
   */
  /*
   * CRM_Export_BAO_Export_Relationship::exportComponents(
   * $this->_selectAll,
   * $this->_componentIds,
   * $this->get('queryParams'),
   * $this->get(CRM_Utils_Sort::SORT_ORDER),
   * NULL,
   * $this->get('returnProperties'),
   * $this->_exportMode,
   * $this->_componentClause,
   * $this->_componentTable,
   * $mergeSameAddress,
   * $mergeSameHousehold,
   * $exportParams,
   * $this->get('queryOperator')
    );
   */
  public static function exportComponents(
  $selectAll, $ids, $params, $order = NULL, $fields = NULL, $moreReturnProperties = NULL, $exportMode = CRM_Export_Form_Select_Relationship::RELATIONSHIP_EXPORT, $componentClause = NULL, $componentTable = NULL, $mergeSameAddress = FALSE, $mergeSameHousehold = FALSE, $exportParams = array(), $queryOperator = 'AND'
  ) {
    $headerRows = $returnProperties = array();
    $paymentFields = $selectedPaymentFields = FALSE;
    $relationField = NULL;

    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
            NULL, NULL, NULL, NULL, TRUE, 'name', FALSE
    );
    $queryMode = CRM_Relationship_BAO_Query::MODE_RELATIONSHIPS;

    // In our case the passed fields are always null, so we set them
    // explicitly
    // Copied from CRM_Relationship_BAO_QUERY should be refactored to seperate
    // method.

    // Add display_name for both contacts
    $contact_fields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE, TRUE);
    $fields['contact_a'] = $contact_fields['display_name'];
    $fields['contact_a']['where'] = 'contact_a.display_name';
    $fields['contact_b'] = $contact_fields['display_name'];
    $fields['contact_b']['where'] = 'contact_b.display_name';
    // Add relationship type field
    $relationship_type_fields = CRM_Contact_BAO_RelationshipType::fields();
    $fields['relationship_type'] = $relationship_type_fields['label_a_b'];
    $fields['relationship_type']['where'] = 'relationship_type.label_a_b';
    // Add custom fields
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Relationship'));

    $returnProperties = CRM_Relationship_BAO_Query::defaultReturnProperties();

    dsm($returnProperties);

    if ($moreReturnProperties) {
      $returnProperties = array_merge($returnProperties, $moreReturnProperties);
    }

    $query = new CRM_Relationship_BAO_Query($params, $returnProperties, NULL, FALSE, FALSE, FALSE, TRUE, $queryOperator
    );

    //sort by state
    //CRM-15301
    $query->_sort = $order;
    list($select, $from, $where, $having) = $query->query();

    $allRelContactArray = $relationQuery = array();

    if (!$selectAll && $componentTable) {
      // TODO For not select all
      //$from .= " INNER JOIN $componentTable ctTable ON ctTable.contact_id = contact_a.id ";
    }
    elseif ($componentClause) {
      if (empty($where)) {
        $where = "WHERE $componentClause";
      }
      else {
        $where .= " AND $componentClause";
      }
    }

    $queryString = "$select $from $where $having";

    $groupBy = "";
    if ($queryMode & CRM_Relationship_BAO_Query::MODE_RELATIONSHIPS && $query->_useGroupBy) {
      $groupBy = " GROUP BY relationship.id";
    }

    $queryString .= $groupBy;

    // always add relationship.id to the ORDER clause
    // so the order is deterministic
    if (strpos('relationship.id', $order) === FALSE) {
      $order .= ", relationship.id";
    }

    if ($order) {
      list($field, $dir) = explode(' ', $order, 2);
      $field = trim($field);
      if (!empty($returnProperties[$field])) {
        //CRM-15301
        $queryString .= " ORDER BY $order";
      }
    }

    $componentDetails = $headerRows = $sqlColumns = array();
    $setHeader = TRUE;

    $rowCount = self::EXPORT_ROW_COUNT;
    $offset = 0;
    // we write to temp table often to avoid using too much memory
    $tempRowCount = 100;

    $count = -1;

    // for CRM-3157 purposes
    $i18n = CRM_Core_I18n::singleton();
    $outputColumns = array();
    //@todo - it would be clearer to start defining output columns earlier in this function rather than stick with return properties until this point
    // as the array is not actually 'returnProperties' after the sql query is formed - making the alterations to it confusing
    foreach ($returnProperties as $key => $value) {
      $outputColumns[$key] = $value;
    }
    while (1) {
      $limitQuery = "{$queryString} LIMIT {$offset}, {$rowCount}";
      dsm($limitQuery);
      $dao = CRM_Core_DAO::executeQuery($limitQuery);
      if ($dao->N <= 0) {
        break;
      }

      while ($dao->fetch()) {
        $count++;
        $row = array();

        //convert the pseudo constants
        // CRM-14398 there is problem in this architecture that is not easily solved. For now we are using the cloned
        // temporary iterationDAO object to get around it.
        // the issue is that the convertToPseudoNames function is adding additional properties (e.g for campaign) to the DAO object
        // these additional properties are NOT reset when the $dao cycles through the while loop
        // nor are they overwritten as they are not in the loop
        // the convertToPseudoNames will not adequately over-write them either as it doesn't 'kick-in' unless the
        // relevant property is set.
        // It may be that a long-term fix could be introduced there - however, it's probably necessary to figure out how to test the
        // export class before tackling a better architectural fix
        $iterationDAO = clone $dao;

        //first loop through output columns so that we return what is required, and in same order.

        dsm($outputColumns);
        foreach ($outputColumns as $field => $value) {
          //we should set header only once
          if ($setHeader) {
            $sqlDone = FALSE;
            $headerRows[] = $query->_fields[$field]['title'];

            if (!$sqlDone) {
              self::sqlColumnDefn($query, $sqlColumns, $field);
            }
          }

          //build row values (data)
          $fieldValue = NULL;
          if (property_exists($iterationDAO, $field)) {
            $fieldValue = $iterationDAO->$field;
          }
          if ($field == 'id') {
            $row[$field] = $iterationDAO->relationship_id;
          }
          elseif (isset($fieldValue) &&
              $fieldValue != ''
          ) {
            //check for custom data
            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($field)) {
              $row[$field] = CRM_Core_BAO_CustomField::getDisplayValue($fieldValue, $cfID, $query->_options);
            }
            else {
              //normal fields with a touch of CRM-3157
              $row[$field] = $fieldValue;
            }
          }
          else {
            // if field is empty or null
            $row[$field] = '';
          }
        }

        dsm($sqlColumns);
        if ($setHeader) {
          $exportTempTable = self::createTempTable($sqlColumns);
        }

        //build header only once
        $setHeader = FALSE;

        // add component info
        // write the row to a file
        $componentDetails[] = $row;

        // output every $tempRowCount rows
        if ($count % $tempRowCount == 0) {
          self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);
          $componentDetails = array();
        }
      }
      $dao->free();
      $offset += $rowCount;
    }

    if ($exportTempTable) {
      self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);

      // do merge same address and merge same household processing
      if ($mergeSameAddress) {
        self::mergeSameAddress($exportTempTable, $headerRows, $sqlColumns, $exportParams);
      }

      // merge the records if they have corresponding households
      if ($mergeSameHousehold) {
        self::mergeSameHousehold($exportTempTable, $headerRows, $sqlColumns, $relationKeyMOH);
        self::mergeSameHousehold($exportTempTable, $headerRows, $sqlColumns, $relationKeyHOH);
      }

      // call export hook
      CRM_Utils_Hook::export($exportTempTable, $headerRows, $sqlColumns, $exportMode);

      // now write the CSV file
      self::writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode);

      // delete the export temp table and component table
      $sql = "DROP TABLE IF EXISTS {$exportTempTable}";
      CRM_Core_DAO::executeQuery($sql);

      CRM_Utils_System::civiExit();
    }
    else {
      CRM_Core_Error::fatal(ts('No records to export'));
    }
  }

}
