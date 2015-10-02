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
class CRM_Relationship_Form_Search_Criteria {

  /**
   * @param CRM_Core_Form $form
   */
  public static function basic(&$form) {
    $form->addElement('hidden', 'hidden_basic', 1);

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
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function contact_a(&$form) {
    $form->addElement('hidden', 'hidden_basic_a', 1);

    // add checkboxes for contact type
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);

    if ($contactTypes) {
      $form->add('select', 'contact_type_a', ts('Contact Type(s)'), $contactTypes, FALSE, array('id' => 'contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
      );
    }
    

    // add text box for last name, first name, street name, city
    $form->addElement('text', 'sort_name_a', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'email_a', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added job title
    $form->addElement('text', 'job_title_a', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->addElement('text', 'contact_id_a', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id'));
    $form->addRule('contact_id_a', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'external_identifier_a', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    // add checkbox for cms users only
    $form->addYesNo('uf_user_a', ts('CMS User?'), TRUE);

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $t = CRM_Core_SelectValues::privacy();
    $form->add('select',
      'privacy_options_a', ts('Privacy'),
      $t,
      FALSE,
      array(
        'id' => 'privacy_options_a',
      'multiple' => 'multiple',
        'class' => 'crm-select2',
      )
    );

    $form->addElement('select',
      'privacy_operator_a', ts('Operator'),
      array(
        'OR' => ts('OR'),
        'AND' => ts('AND'),
      )
    );

    $options = array(
      1 => ts('Exclude'),
      2 => ts('Include by Privacy Option(s)'),
    );
    $form->addRadio('privacy_toggle_a', ts('Privacy Options'), $options, array('allowClear' => FALSE));

    // preferred communication method
    $comm = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');

    $commPreff = array();
    foreach ($comm as $k => $v) {
      $commPreff[] = $form->createElement('advcheckbox', $k . '_a', NULL, $v);
    }

    $onHold[] = $form->createElement('advcheckbox', 'on_hold', NULL, '');
    $form->addGroup($onHold, 'email_on_hold_a', ts('Email On Hold'));

    $form->addGroup($commPreff, 'preferred_communication_method_a', ts('Preferred Communication Method'));

    // Phone search
    $form->addElement('text', 'phone_numeric_a', ts('Phone Number'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'phone_location_type_id_a', ts('Phone Location'), array('' => ts('- any -')) + $locationType, FALSE, array('class' => 'crm-select2'));
    $form->add('select', 'phone_phone_type_id_a', ts('Phone Type'), array('' => ts('- any -')) + $phoneType, FALSE, array('class' => 'crm-select2'));

    // add all the custom  searchable fields
    $contact = array('Individual');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $contact);
    if ($groupDetails) {
      $form->assign('contactGroupTree', $groupDetails);
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
   * @param CRM_Core_Form $form
   */
  public static function contact_b(&$form) {
    $form->addElement('hidden', 'hidden_basic_b', 1);

    // add checkboxes for contact type
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);

    if ($contactTypes) {
      $form->add('select', 'contact_type_b', ts('Contact Type(s)'), $contactTypes, FALSE, array('id' => 'contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
      );
    }


    // add text box for last name, first name, street name, city
    $form->addElement('text', 'sort_name_b', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'email_b', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added job title
    $form->addElement('text', 'job_title_b', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->addElement('text', 'contact_id_b', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id'));
    $form->addRule('contact_id_b', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'external_identifier_b', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    // add checkbox for cms users only
    $form->addYesNo('uf_user_b', ts('CMS User?'), TRUE);

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $t = CRM_Core_SelectValues::privacy();
    $form->add('select', 'privacy_options_b', ts('Privacy'), $t, FALSE, array(
      'id' => 'privacy_options',
      'multiple' => 'multiple',
      'class' => 'crm-select2',
        )
    );

    $form->addElement('select', 'privacy_operator_b', ts('Operator'), array(
      'OR' => ts('OR'),
      'AND' => ts('AND'),
        )
    );

    $options = array(
      1 => ts('Exclude'),
      2 => ts('Include by Privacy Option(s)'),
    );
    $form->addRadio('privacy_toggle_b', ts('Privacy Options'), $options, array('allowClear' => FALSE));

    // preferred communication method
    $comm = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');

     $commPreff = array();
    foreach ($comm as $k => $v) {
      $commPreff[] = $form->createElement('advcheckbox', $k . '_b', NULL, $v);
    }

    $onHold[] = $form->createElement('advcheckbox', 'on_hold_b', NULL, '');
    $form->addGroup($onHold, 'email_on_hold_b', ts('Email On Hold'));

    $form->addGroup($commPreff, 'preferred_communication_method_b', ts('Preferred Communication Method'));

    //CRM-6138 Preferred Language
    //$form->addSelect('preferred_language', array('class' => 'twenty', 'context' => 'search'));
    // Phone search
    $form->addElement('text', 'phone_numeric_b', ts('Phone Number'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'phone_location_type_id_b', ts('Phone Location'), array('' => ts('- any -')) + $locationType, FALSE, array('class' => 'crm-select2'));
    $form->add('select', 'phone_phone_type_id_b', ts('Phone Type'), array('' => ts('- any -')) + $phoneType, FALSE, array('class' => 'crm-select2'));

    // add all the custom  searchable fields
    $contact = array('Organization');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $contact);
    if ($groupDetails) {
      $form->assign('contactGroupTree', $groupDetails);
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

}
