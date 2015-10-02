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
{include file="CRM/common/pager.tpl" location="top"}

<a href="#" class="crm-selection-reset crm-hover-button"><span class="icon ui-icon-close"></span> {ts}Reset all selections{/ts}</a>

<table summary="{ts}Search results listings.{/ts}" class="selector row-highlight">
  <thead class="sticky">
    <tr>
      <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
      {foreach from=$columnHeaders item=header}
        <th scope="col">
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {else}
            {$header.name}
          {/if}
        </th>
      {/foreach}
    </tr>
  </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
    <tr id="rowid{$row.relationship_id}" class="{cycle values='odd-row,even-row'}">
      {assign var=cbName value=$row.checkbox}
      <td>{$form.$cbName.html}</td>
      <td>{$row.relationship_type}</td>
      {foreach from=$row item=value key=key}
        {if ($key neq "checkbox") and ($key neq "action") and ($key neq "status") and ($key neq "sort_name") and ($key neq "relationship_id")}
          <td>{$value}&nbsp;</td>
        {/if}
      {/foreach}
      <td style='width:125px;'>{$row.action|replace:'xx':$row.relationship_id}</td>
    </tr>
  {/foreach}

  </table>

  <script type="text/javascript">
    {literal}
      CRM.$(function ($) {
        // Clear any old selection that may be lingering in quickform
        $("input.select-row, input.select-rows", 'form.crm-search-form').prop('checked', false).closest('tr').removeClass('crm-row-selected');
        // Retrieve stored checkboxes
        var relids = {/literal}{$selectedRelationshipIds|@json_encode}{literal};
        if (relids.length > 0) {
          $('#mark_x_' + relids.join(',#mark_x_') + ',input[name=radio_ts][value=ts_sel]').prop('checked', true);
        }
      });
    {/literal}
  </script>
  {include file="CRM/common/pager.tpl" location="bottom"}
