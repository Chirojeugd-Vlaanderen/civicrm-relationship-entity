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
      {$form.sort_name_a.html}
    </td>
    <td>
      <label>{ts}Complete OR Partial Email{/ts}</label><br />
      {$form.email_a.html}
    </td>
    <td class="adv-search-top-submit" colspan="2">
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
      </div>
    </td>
  </tr>
  <tr>
    {if $form.contact_type_a}
      <td><label>{ts}Contact Type(s){/ts}</label><br />
              {$form.contact_type_a.html}
            </td>
  {else}
    <td>&nbsp;</td>
  {/if}
  <tr>
    <td>
      <div>
        {$form.phone_numeric_a.label}<br />{$form.phone_numeric_a.html}
      </div>
      <div class="description font-italic">
        {ts}Punctuation and spaces are ignored.{/ts}
      </div>
    </td>
    <td>{$form.phone_location_type_id_a.label}<br />{$form.phone_location_type_id_a.html}</td>
    <td>{$form.phone_phone_type_id_a.label}<br />{$form.phone_phone_type_id_a.html}</td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="form-layout-compressed">
      <tr>
        <td colspan="2">
          {$form.privacy_toggle_a.html} {help id="id-privacy"}
        </td>
      </tr>
      <tr>
        <td>
          {$form.privacy_options_a.html}
        </td>
        <td style="vertical-align:middle">
          <div id="privacy-operator-wrapper_a">{$form.privacy_operator_a.html} {help id="privacy-operator"}</div>
        </td>
      </tr>
      </table>
      {literal}
        <script type="text/javascript">
          cj("select#privacy_options_a").change(function() {
            if (cj(this).val() && cj(this).val().length > 1) {
              cj('#privacy-operator-wrapper_a').show();
            } else {
              cj('#privacy-operator-wrapper_a').hide();
            }
          }).change();
        </script>
      {/literal}
    </td>
    <td colspan="3">
      {$form.preferred_communication_method_a.label}<br />
      {$form.preferred_communication_method_a.html}<br />
      <div class="spacer"></div>
      {$form.email_on_hold_a.html} {$form.email_on_hold_a.label}
    </td>
  </tr>
  <tr>
    <td>
      {if $form.uf_user_a}
        {$form.uf_user_a.label} {$form.uf_user_a.html}
        <div class="description font-italic">
              {ts 1=$config->userFramework}Does the contact have a %1 Account?{/ts}
          </div>
      {else}
          &nbsp;
      {/if}
    </td>
    <td>
      {$form.job_title_a.label}<br />
      {$form.job_title_a.html}
    </td>
  </tr>
  <tr>
    <td>
      {$form.contact_id_a.label} {help id="id-internal-id" file="CRM/Contact/Form/Contact"}<br />
      {$form.contact_id_a.html}
    </td>
    <td>
      {$form.external_identifier_a.label} {help id="id-external-id" file="CRM/Contact/Form/Contact"}<br />
      {$form.external_identifier_a.html}
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
