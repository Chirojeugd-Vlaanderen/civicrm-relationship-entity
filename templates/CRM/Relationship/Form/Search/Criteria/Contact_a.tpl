{*
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
*}
<table class="form-layout">
  <tr>
    <td><label>{ts}Complete OR Partial Name{/ts}</label><br />
      {$form.contact_a_display_name.html}
    </td>
    <td>
      <label>{ts}Complete OR Partial Email{/ts}</label><br />
      {$form.contact_a_email.html}
    </td>
    <td class="adv-search-top-submit" colspan="2">
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
      </div>
    </td>
  </tr>
  <tr>
    {if $form.contact_a_contact_type}
      <td><label>{ts}Contact Type(s){/ts}</label><br />
        {$form.contact_a_contact_type.html}
      </td>
  {else}
    <td>&nbsp;</td>
  {/if}
  <tr>
    <td>
      <div>
        {$form.contact_a_phone_numeric.label}<br />{$form.contact_a_phone_numeric.html}
      </div>
      <div class="description font-italic">
        {ts}Punctuation and spaces are ignored.{/ts}
      </div>
    </td>
    <td>{$form.contact_a_phone_location_type_id.label}<br />{$form.contact_a_phone_location_type_id.html}</td>
    <td>{$form.contact_a_phone_phone_type_id.label}<br />{$form.contact_a_phone_phone_type_id.html}</td>
  </tr>

  <tr>
    <td>
      <div id="contact_a_streetAddress" class="crm-field-wrapper">
        {$form.contact_a_street_address.label}<br />
        {$form.contact_a_street_address.html|crmAddClass:big}
      </div>
         
      <div class="crm-field-wrapper">
        {$form.contact_a_city.label}<br />
        {$form.contact_a_city.html}
      </div>
    </td>

    <td>
      <div class="crm-field-wrapper">
        <div>{$form.contact_a_location_type.label} {help id="location_type" title=$form.location_type.label}</div>
        {$form.contact_a_location_type.html}
      </div>
      {if $form.contact_a_postal_code.html}
        <div class="crm-field-wrapper">
          {$form.contact_a_postal_code.label}
          <input type="checkbox" id="contact_a_postal-code-range-toggle" value="1"/>
          <label for="contact_a_postal-code-range-toggle">{ts}Range{/ts}</label><br />
          <div class="contact_a_postal_code-wrapper">
            {$form.contact_a_postal_code.html}
          </div>
          <div class="contact_a_postal_code_range-wrapper" style="display: none;">
              {$form.contact_a_postal_code_low.html}&nbsp;-&nbsp;{$form.contact_a_postal_code_high.html}
          </div>
        </div>
        <script type="text/javascript">
          {literal}
          CRM.$(function($) {
            $('#contact_a_postal-code-range-toggle').change(function() {
              if ($(this).is(':checked')) {
                $('.contact_a_postal_code_range-wrapper').show();
                $('.contact_a_postal_code-wrapper').hide().find('input').val('');
              } else {
                $('.contact_a_postal_code-wrapper').show();
                $('.contact_a_postal_code_range-wrapper').hide().find('input').val('');
              }
            });
          if ($('#contact_a_postal_code_low').val() || $('#contact_a_postal_code_high').val()) {
              $('#contact_a_postal-code-range-toggle').prop('checked', true).change();
            }
          });
          {/literal}
        </script>
      {/if}
    </td>
  </tr>

  {if $addressGroupTree}
    <tr>
      <td colspan="2">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$addressGroupTree showHideLinks=false}
      </td>
    </tr>
  {/if}

  <tr>
  <tr>
    <td>
      <label>{ts}Birth Dates{/ts}</label>
    </td>
  </tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="contact_a_birth_date" from='_low' to='_high'}
</tr>
<tr>
  <td>
    {$form.contact_a_is_deceased.label}<br />
    {$form.contact_a_is_deceased.html}
  </td>
</tr>
<tr>
<tr>
  <td>
    <label>{ts}Deceased Dates{/ts}</label>
  </td>
</tr>
{include file="CRM/Core/DateRange.tpl" fieldName="contact_a_deceased_date" from='_low' to='_high'}
</tr>
<tr>
  <td>
    {$form.contact_a_gender_id.label}<br />
    {$form.contact_a_gender_id.html}
  </td>
</tr>

<tr>
  <td>
    {$form.contact_a_job_title.label}<br />
      {$form.contact_a_job_title.html}
    </td>
    <td>
      {$form.contact_a_contact_id.label} {help id="id-internal-id" file="CRM/Contact/Form/Contact"}<br />
      {$form.contact_a_contact_id.html}
    </td>
    <td>
      {$form.contact_a_external_identifier.label} {help id="id-external-id" file="CRM/Contact/Form/Contact"}<br />
      {$form.contact_a_external_identifier.html}
    </td>
  </tr>
  {if $contactGroupTree}
    <tr>
      <td colspan="2">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$contactGroupTree showHideLinks=false}
      </td>
    </tr>
  {/if}
</table>
