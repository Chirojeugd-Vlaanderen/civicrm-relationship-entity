<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CRM_Relationship_Export_Form_Select extends CRM_Export_Form_Select {

  /**
   * Export modes.
   */
  const
      RELATIONSHIP_EXPORT = 9;

  /**
   * Current export mode.
   *
   * @var int
   */
  public $_exportMode;
  public $_componentTable;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    $this->_selectAll = FALSE;
    $this->_exportMode = self::RELATIONSHIP_EXPORT;
    $this->_componentIds = array();
    $this->_componentClause = NULL;


    // we need to determine component export
    $stateMachine = $this->controller->getStateMachine();

    $formName = CRM_Utils_System::getClassName($stateMachine);
    $componentName = explode('_', $formName);


    $className = "CRM_Relationship_Form_Task";
    $className::preProcessCommon($this, TRUE);
    $values = $this->controller->exportValues('Search');
   
    $count = 0;
    $this->_matchingContacts = FALSE;
    if (CRM_Utils_Array::value('radio_ts', $values) == 'ts_sel') {
      foreach ($values as $key => $value) {
        if (strstr($key, 'mark_x')) {
          $count++;
        }
        if ($count > 2) {
          $this->_matchingContacts = TRUE;
          break;
        }
      }
    }

    $this->_task = $values['task'];

    $this->assign('taskName', "Export $componentName[1]");
    $className = "CRM_{$componentName[1]}_Task";
    $componentTasks = $className::tasks();
    $taskName = $componentTasks[$this->_task];
    $component = TRUE;
    
    if ($this->_componentTable) {
      $query = "SELECT count(*) FROM {$this->_componentTable}";
      $totalSelectedRecords = CRM_Core_DAO::singleValueQuery($query);
    }
    else {
      $totalSelectedRecords = count($this->_componentIds);
    }
    $this->assign('totalSelectedRecords', $totalSelectedRecords);
    $this->assign('taskName', $taskName);
    $this->assign('component', $component);
    // all records actions = save a search
    if (($values['radio_ts'] == 'ts_all') || ($this->_task == CRM_Contact_Task::SAVE_SEARCH)) {
      $this->_selectAll = TRUE;
      $rowCount = $this->get('rowCount');
      if ($rowCount > 2) {
        $this->_matchingContacts = TRUE;
      }
      $this->assign('totalSelectedRecords', $rowCount);
    }

    $this->assign('matchingContacts', $this->_matchingContacts);
    $this->set('componentIds', $this->_componentIds);
    $this->set('selectAll', $this->_selectAll);
    $this->set('exportMode', $this->_exportMode);
    $this->set('componentClause', $this->_componentClause);
    $this->set('componentTable', $this->_componentTable);
  }

  /**
   * Build mapping form element.
   */
  public function buildMapping() {
    $exportType = 'Export Relations';

    $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', $exportType, 'name');
    $this->set('mappingTypeId', $mappingTypeId);

    $mappings = CRM_Core_BAO_Mapping::getMappings($mappingTypeId);
    if (!empty($mappings)) {
      $this->add('select', 'mapping', ts('Use Saved Field Mapping'), array('' => '-select-') + $mappings);
    }
  }

  /**
   * Use the tpl of the base class
   *
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Export/Form/Select.tpl';
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportOption = $params['exportOption'];
    $mergeSameAddress = CRM_Utils_Array::value('mergeOption', $params) == self::EXPORT_MERGE_SAME_ADDRESS ? 1 : 0;
    $mergeSameHousehold = CRM_Utils_Array::value('mergeOption', $params) == self::EXPORT_MERGE_HOUSEHOLD ? 1 : 0;

    $this->set('mergeSameAddress', $mergeSameAddress);
    $this->set('mergeSameHousehold', $mergeSameHousehold);

    // instead of increasing the number of arguments to exportComponents function, we
    // will send $exportParams as another argument, which is an array and suppose to contain
    // all submitted options or any other argument
    $exportParams = $params;

    $mappingId = CRM_Utils_Array::value('mapping', $params);
    if ($mappingId) {
      $this->set('mappingId', $mappingId);
    }
    else {
      $this->set('mappingId', NULL);
    }

    if ($exportOption == self::EXPORT_ALL) {
      CRM_Export_BAO_Export_Relationship::exportComponents($this->_selectAll, $this->_componentIds, $this->get('queryParams'), $this->get(CRM_Utils_Sort::SORT_ORDER), NULL, $this->get('returnProperties'), $this->_exportMode, $this->_componentClause, $this->_componentTable, $mergeSameAddress, $mergeSameHousehold, $exportParams, $this->get('queryOperator')
      );
    }

    //reset map page
    $this->controller->resetPage('Map');
  }

}
