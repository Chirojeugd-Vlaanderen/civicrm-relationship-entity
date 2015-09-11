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

{strip}
<table class="selector row-highlight">
  <thead class="sticky">
  <tr>
    {if !$single and $context eq 'Search' }
    <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
    {/if}
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
    <tr id="rowid{$row.relationship_id}" class="{cycle values="odd-row,even-row"} crm-relationship_{$row.relationship_id}">
      {if !$single and $context eq 'Search' }
        {assign var=cbName value=$row.checkbox}
        <td>{$form.$cbName.html}</td>
      {/if}
    <td class="crm-relationship-contact_a">
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id_a`&key=`$qfKey`&context=`$context`"}">{$row.contact_a}</a>
    </td>
    <td class="crm-relationship-relationship_type">{$row.relationship_type}</td>
    <td class="crm-relationship-contact_b">
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id_b`&key=`$qfKey`&context=`$context`"}">{$row.contact_b}</a>
    </td>
    <td class="crm-relationship-start_date">{$row.start_date}</td>
    <td class="crm-relationship-end_date">{$row.end_date}</td>
    <td class="crm-relationship-is_active">
      {if $row.is_active eq '1'}
        Actief
      {else}
        Niet-actief
      {/if}</td>
    <td class="crm-relationship-description">{$row.description}</td>
    <td>{$row.action|replace:'xx':$row.id}</td>
    </tr>
  {/foreach}

</table>
{/strip}

{include file="CRM/common/pager.tpl" location="bottom"}
