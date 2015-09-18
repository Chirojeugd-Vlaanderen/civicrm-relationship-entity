<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class CRM_Core_BAO_Mapping_Relationship extends CRM_Core_BAO_Mapping {

  /**
   * Build the mapping form.
   *
   * @param CRM_Core_Form $form
   * @param string $mappingType
   *   (Export/Import/Search Builder).
   * @param int $mappingId
   * @param int $columnNo
   * @param int $blockCount
   *   (no of blocks shown).
   * @param NULL $exportMode
   *
   * @return void
   */
  public static function buildMappingForm(&$form, $mappingType = 'Export', $mappingId = NULL, $columnNo, $blockCount = 3, $exportMode = NULL) {
      $name = "Map";
    $columnCount = array('1' => $columnNo);

    $form->applyFilter('saveMappingName', 'trim');

      //to save the current mappings
      if (!isset($mappingId)) {
        $saveDetailsName = ts('Save this field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }
      else {
        $form->assign('loadedMapping', $mappingId);

        $params = array('id' => $mappingId);
        $temp = array();
        $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

        $form->assign('savedName', $mappingDetails->name);

        $form->add('hidden', 'mappingId', $mappingId);

        $form->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
        $saveDetailsName = ts('Save as a new field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }

      $form->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));
      $form->addFormRule(array('CRM_Export_Form_Map', 'formRule'), $form->get('mappingTypeId'));
    

    $defaults = array();
    $hasLocationTypes = array();
    $hasRelationTypes = array();
    $fields = array();

    if ($mappingType == 'Export') {
      $required = TRUE;
    }
    

    $fields = CRM_Contact_BAO_Relationship::fields();
    // add custom fields
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Relationship'));
    ksort($fields);

    // add component fields
    $compArray = array();

    foreach ($fields as $key => $value) {
      //CRM-2676, replacing the conflict for same custom field name from different custom group.
      $customGroupName = self::getCustomGroupName($key);
      if ($customGroupName) {
        $relatedMapperFields[$key] = $mapperFields[$key] = $customGroupName . ': ' . $value['title'];
      }
      else {
        $relatedMapperFields[$key] = $mapperFields[$key] = $value['title'];
      }
    }

    $mapperKeys = array_keys($mapperFields);

    $sel1 = array('' => ts('- select field -')) + $mapperFields;
    if (isset($mappingId)) {
      $colCnt = 0;

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider,
          $mappingRelation, $mappingOperator, $mappingValue
          ) = CRM_Core_BAO_Mapping::getMappingFields($mappingId);

      $blkCnt = count($mappingName);
      if ($blkCnt >= $blockCount) {
        $blockCount = $blkCnt + 1;
      }
      for ($x = 1; $x < $blockCount; $x++) {
        if (isset($mappingName[$x])) {
          $colCnt = count($mappingName[$x]);
          if ($colCnt >= $columnCount[$x]) {
            $columnCount[$x] = $colCnt;
          }
        }
      }
    }

    $form->_blockCount = $blockCount;
    $form->_columnCount = $columnCount;

    $form->set('blockCount', $form->_blockCount);
    $form->set('columnCount', $form->_columnCount);

    $defaults = $noneArray = $nullArray = array();

    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;

    for ($x = 1; $x < $blockCount; $x++) {

      for ($i = 0; $i < $columnCount[$x]; $i++) {

        $sel = &$form->addElement('hierselect', "mapper[$x][$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
        $jsSet = FALSE;

        if (isset($mappingId)) {
          //TODO opgeslagen mappings
        }
        //Fix for Export

        $j = 7;
       

        $formValues = $form->exportValues();
        if (!$jsSet) {
          if (empty($formValues)) {
            // Incremented length for third select box(relationship type)
            for ($k = 1; $k < $j; $k++) {
              $noneArray[] = array($x, $i, $k);
            }
          }
          else {
            if (!empty($formValues['mapper'][$x])) {
              foreach ($formValues['mapper'][$x] as $value) {
                for ($k = 1; $k < $j; $k++) {
                  if (!isset($formValues['mapper'][$x][$i][$k]) ||
                      (!$formValues['mapper'][$x][$i][$k])
                  ) {
                    $noneArray[] = array($x, $i, $k);
                  }
                  else {
                    $nullArray[] = array($x, $i, $k);
                  }
                }
              }
            }
            else {
              for ($k = 1; $k < $j; $k++) {
                $noneArray[] = array($x, $i, $k);
              }
            }
          }
        }
        //Fix for Export

        $sel->setOptions(array($sel1));
      }
      $title = ts('Select more fields');
      

      $form->addElement('submit', "addMore[$x]", $title, array('class' => 'submit-link'));
    }
    //end of block for

    $js = "<script type='text/javascript'>\n";
    $formName = "document.{$name}";
    if (!empty($nullArray)) {
      $js .= "var nullArray = [";
      $elements = array();
      $seen = array();
      foreach ($nullArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for (var i=0;i<nullArray.length;i++) {
                    if ( {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'] ) {
                        {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'].style.display = '';
                    }
                }
";
    }
    if (!empty($noneArray)) {
      $js .= "var noneArray = [";
      $elements = array();
      $seen = array();
      foreach ($noneArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for (var i=0;i<noneArray.length;i++) {
                    if ( {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'] ) {
  {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'].style.display = 'none';
                    }
                }
";
    }
    $js .= "</script>\n";

    $form->assign('initHideBoxes', $js);
    $form->assign('columnCount', $columnCount);
    $form->assign('blockCount', $blockCount);
    $form->setDefaults($defaults);

    $form->setDefaultAction('refresh');
  }

}
