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
   * Generate the custom Data Fields based
   * on the is_searchable
   *
   *
   * @param $form
   *
   * @return void
   */
  public static function custom(&$form) {
    $form->add('hidden', 'hidden_custom', 1);
    $extends = array('Relationship');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends
    );

    $form->assign('groupTree', $groupDetails);

    foreach ($groupDetails as $key => $group) {
      $_groupTitle[$key] = $group['name'];
      CRM_Core_ShowHideBlocks::links($form, $group['name'], '', '');

      $groupId = $group['id'];
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $elementName = 'custom_' . $fieldId;

        CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, FALSE, TRUE
        );
      }
    }
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function contact_a(&$form) {
    $form->addElement('hidden', 'hidden_contact_a', 1);

    // add checkboxes for contact type
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);

    if ($contactTypes) {
      $form->add('select', 'contact_a_contact_type', ts('Contact Type(s)'), $contactTypes, FALSE, array('id' => 'contact_a_contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
      );
    }
    

    // add text box for last name, first name, street name, city
    $form->addElement('text', 'contact_a_display_name', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'contact_a_email', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added job title
    $form->addElement('text', 'contact_a_job_title', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->addElement('text', 'contact_a_contact_id', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id'));
    $form->addRule('contact_a_contact_id', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'contact_a_external_identifier', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    // Phone search
    $form->addElement('text', 'contact_a_phone_numeric', ts('Phone Number'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'contact_a_phone_location_type_id', ts('Phone Location'), array('' => ts('- any -')) + $locationType, FALSE, array('class' => 'crm-select2'));
    $form->add('select', 'contact_a_phone_phone_type_id', ts('Phone Type'), array('' => ts('- any -')) + $phoneType, FALSE, array('class' => 'crm-select2'));

    // add all the custom  searchable fields
    $contact = array('Individual', 'Household', 'Organization');
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

    // Add address fields
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $form->addElement('text', 'contact_a_street_address', ts('Street Address'), $attributes['street_address']);
    $form->addElement('text', 'contact_a_city', ts('City'), $attributes['city']);
    $form->addElement('text', 'contact_a_postal_code', ts('Zip / Postal Code'), $attributes['postal_code']);
    $form->addElement('text', 'contact_a_postal_code_low', NULL, $attributes['postal_code'] + array('placeholder' => ts('From')));
    $form->addElement('text', 'contact_a_postal_code_high', NULL, $attributes['postal_code'] + array('placeholder' => ts('To')));

    // select for location type
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $form->add('select', 'contact_a_location_type', ts('Address Location'), $locationType, FALSE, array(
      'multiple' => TRUE,
      'class' => 'crm-select2',
      'placeholder' => ts('Primary'),
    ));

    // custom data extending addresses -
    $extends = array('Address');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('addressGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $elementName = 'custom_' . $field['id'];
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $field['id'], FALSE, FALSE, TRUE
          );
        }
      }
    }

    // Add demographics
    // radio button for gender
    $genderOptions = array();
    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL, ts('Gender'), $var, $key, array('id' => "civicrm_gender_{$var}_{$key}")
      );
    }
    $form->addGroup($genderOptions, 'contact_a_gender_id', ts('Gender'))->setAttribute('allowClear', TRUE);

    CRM_Core_Form_Date::buildDateRange($form, 'contact_a_birth_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    CRM_Core_Form_Date::buildDateRange($form, 'contact_a_deceased_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    // radio button for is_deceased
    $form->addYesNo('contact_a_is_deceased', ts('Deceased'), TRUE);
  }

 /**
   * @param CRM_Core_Form $form
   */
  public static function contact_b(&$form) {
    $form->addElement('hidden', 'hidden_contact_b', 1);

    // add checkboxes for contact type
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);

    if ($contactTypes) {
      $form->add('select', 'contact_b_contact_type', ts('Contact Type(s)'), $contactTypes, FALSE, array('id' => 'contact_b_contact_type', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 100%;')
      );
    }


    // add text box for last name, first name, street name, city
    $form->addElement('text', 'contact_b_display_name', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'contact_b_email', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added job title
    $form->addElement('text', 'contact_b_job_title', ts('Job Title'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'job_title'));

    //added internal ID
    $form->addElement('text', 'contact_b_contact_id', ts('Contact ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'id'));
    $form->addRule('contact_b_contact_id', ts('Please enter valid Contact ID'), 'positiveInteger');

    //added external ID
    $form->addElement('text', 'contact_b_external_identifier', ts('External ID'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'));

    // Phone search
    $form->addElement('text', 'contact_b_phone_numeric', ts('Phone Number'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $form->add('select', 'contact_b_phone_location_type_id', ts('Phone Location'), array('' => ts('- any -')) + $locationType, FALSE, array('class' => 'crm-select2'));
    $form->add('select', 'contact_b_phone_phone_type_id', ts('Phone Type'), array('' => ts('- any -')) + $phoneType, FALSE, array('class' => 'crm-select2'));

    // add all the custom  searchable fields
    $contact = array('Individual', 'Household', 'Organization');
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

    // Add address fields
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $form->addElement('text', 'contact_b_street_address', ts('Street Address'), $attributes['street_address']);
    $form->addElement('text', 'contact_b_city', ts('City'), $attributes['city']);
    $form->addElement('text', 'contact_b_postal_code', ts('Zip / Postal Code'), $attributes['postal_code']);
    $form->addElement('text', 'contact_b_postal_code_low', NULL, $attributes['postal_code'] + array('placeholder' => ts('From')));
    $form->addElement('text', 'contact_b_postal_code_high', NULL, $attributes['postal_code'] + array('placeholder' => ts('To')));

    // select for location type
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $form->add('select', 'contact_b_location_type', ts('Address Location'), $locationType, FALSE, array(
      'multiple' => TRUE,
      'class' => 'crm-select2',
      'placeholder' => ts('Primary'),
    ));

    // custom data extending addresses -
    $extends = array('Address');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('addressGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $elementName = 'custom_' . $field['id'];
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $field['id'], FALSE, FALSE, TRUE
          );
        }
      }
    }

    // Add demographics
    // radio button for gender
    $genderOptions = array();
    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL, ts('Gender'), $var, $key, array('id' => "civicrm_gender_{$var}_{$key}")
      );
    }
    $form->addGroup($genderOptions, 'contact_b_gender_id', ts('Gender'))->setAttribute('allowClear', TRUE);

    CRM_Core_Form_Date::buildDateRange($form, 'contact_b_birth_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    CRM_Core_Form_Date::buildDateRange($form, 'contact_b_deceased_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    // radio button for is_deceased
    $form->addYesNo('contact_b_is_deceased', ts('Deceased'), TRUE);
  }

}
