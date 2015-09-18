<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class CRM_Relationship_Export_Form_Map extends CRM_Export_Form_Map {

  public function buildQuickForm() {
      CRM_Core_BAO_Mapping_Relationship::buildMappingForm($this, 'Export', $this->_mappingId, $this->_exportColumnCount, $blockCnt = 2, $this->get('exportMode')
    );

    $this->addButtons(array(
      array(
        'type' => 'back',
        'name' => ts('Previous'),
      ),
      array(
        'type' => 'next',
        'name' => ts('Export'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      ),
      array(
        'type' => 'done',
        'icon' => 'close',
        'name' => ts('Done'),
      ),
    ));
  } 

  /**
   * Use the tpl of the base class
   *
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Export/Form/Map.tpl';
  } 

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportParams = $this->controller->exportValues('Select');

    $currentPath = CRM_Utils_System::currentPath();

    $urlParams = NULL;
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams = "&qfKey=$qfKey";
    }

    //get the button name
    $buttonName = $this->controller->getButtonName('done');
    $buttonName1 = $this->controller->getButtonName('next');
    if ($buttonName == '_qf_Map_done') {
      $this->set('exportColumnCount', NULL);
      $this->controller->resetPage($this->_name);
      return CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, 'force=1' . $urlParams));
    }

    if ($this->controller->exportValue($this->_name, 'addMore')) {
      $this->set('exportColumnCount', $this->_exportColumnCount);
      return;
    }
    $mapperKeys = $params['mapper'][1];
    $checkEmpty = 0;
    foreach ($mapperKeys as $value) {
      if ($value[0]) {
        $checkEmpty++;
      }
    }

    if (!$checkEmpty) {
      $this->set('mappingId', NULL);
      CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, '_qf_Map_display=true' . $urlParams));
    }

    if ($buttonName1 == '_qf_Map_next') {
      if (!empty($params['updateMapping'])) {
        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $params['mappingId']);
      }

      if (!empty($params['saveMapping'])) {
        $mappingParams = array(
          'name' => $params['saveMappingName'],
          'description' => $params['saveMappingDesc'],
          'mapping_type_id' => $this->get('mappingTypeId'),
        );

        $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $saveMapping->id);
      }
    }

    //get the csv file
    CRM_Export_BAO_Export_Relationship::exportComponents($this->get('selectAll'), $this->get('componentIds'), $this->get('queryParams'), $this->get(CRM_Utils_Sort::SORT_ORDER), $mapperKeys, $this->get('returnProperties'), $this->get('exportMode'), $this->get('componentClause'), $this->get('componentTable'), $this->get('mergeSameAddress'), $this->get('mergeSameHousehold'), $exportParams);
  }
   
}
