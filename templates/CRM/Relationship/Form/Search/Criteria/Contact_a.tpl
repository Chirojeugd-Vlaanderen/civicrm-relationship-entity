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
      {$form.contact_a_sort_name.html}
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
    <td colspan="2">
      <table class="form-layout-compressed">
      <tr>
        <td colspan="2">
          {$form.contact_a_privacy_toggle.html} {help id="id-privacy"}
        </td>
      </tr>
      <tr>
        <td>
          {$form.contact_a_privacy_options.html}
        </td>
        <td style="vertical-align:middle">
          <div id="privacy-operator-wrapper_a">{$form.contact_a_privacy_operator.html} {help id="privacy-operator"}</div>
        </td>
      </tr>
      </table>
      {literal}
        <script type="text/javascript">
          cj("select#contact_a_privacy_options").change(function() {
            if (cj(this).val() && cj(this).val().length > 1) {
              cj('#contact_a_privacy-operator-wrapper').show();
            } else {
              cj('#contact_a_privacy-operator-wrapper').hide();
            }
          }).change();
        </script>
      {/literal}
    </td>
    <td colspan="3">
      {$form.contact_a_preferred_communication_method.label}<br />
      {$form.contact_a_preferred_communication_method.html}<br />
      <div class="spacer"></div>
      {$form.contact_a_email_on_hold.html} {$form.contact_a_email_on_hold.label}
    </td>
  </tr>
  <tr>
    <td>
      {if $form.contact_a_uf_user}
        {$form.contact_a_uf_user.label} {$form.contact_a_uf_user.html}
        <div class="description font-italic">
              {ts 1=$config->userFramework}Does the contact have a %1 Account?{/ts}
          </div>
      {else}
          &nbsp;
      {/if}
    </td>
    <td>
      {$form.contact_a_job_title.label}<br />
      {$form.contact_a_job_title.html}
    </td>
  </tr>
  <tr>
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
