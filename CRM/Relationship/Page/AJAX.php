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
 *
 */

/**
 * This class contains all contact related functions that are called using AJAX (jQuery)
 */
class CRM_Relationship_Page_AJAX {

  /**
   * Used to store selected contacts across multiple pages in advanced search.
   */
  public static function selectUnselectRelationships() {
    $name = CRM_Utils_Array::value('name', $_REQUEST);
    $cacheKey = CRM_Utils_Array::value('qfKey', $_REQUEST);
    $state = CRM_Utils_Array::value('state', $_REQUEST, 'checked');
    $variableType = CRM_Utils_Array::value('variableType', $_REQUEST, 'single');

    $actionToPerform = CRM_Utils_Array::value('action', $_REQUEST, 'select');

    if ($variableType == 'multiple') {
      // action post value only works with multiple type variable
      if ($name) {
        //multiple names like mark_x_1-mark_x_2 where 1,2 are cids
        $elements = explode('-', $name);
        foreach ($elements as $key => $element) {
          $elements[$key] = self::_convertToId($element);
        }
        CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $actionToPerform, $elements, 'civicrm_relationship');
      }
      else {
        CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $actionToPerform, NULL, 'civicrm_relationship');
      }
    }
    elseif ($variableType == 'single') {
      $cId = self::_convertToId($name);
      $action = ($state == 'checked') ? 'select' : 'unselect';
      CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $action, $cId, 'civicrm_relationship');
    }
    $contactIds = CRM_Core_BAO_PrevNextCache::getSelection($cacheKey, 'get', 'civicrm_relationship');
    $countSelectionCids = count($contactIds[$cacheKey]);

    $arrRet = array('getCount' => $countSelectionCids);
    CRM_Utils_JSON::output($arrRet);
  }

  /**
   * @param string $name
   *
   * @return string
   */
  public static function _convertToId($name) {
    if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
      $cId = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
    }
    return $cId;
  }

}
