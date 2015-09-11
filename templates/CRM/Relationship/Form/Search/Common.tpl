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
<tr>
  <td><label>{$form.relationship_type_id.label}</label>&nbsp;
    {$form.relationship_type_id.html|crmAddClass:twenty}
  </td>
  <td>
    <label>{$form.is_active.label}</label>&nbsp;
    {$form.is_active.html}
  </td>
</tr>
<tr>
  <td colspan="2"><label>{ts}Start Date{/ts}</label></td>
</tr>
<tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="start_date" from='_low' to='_high'}
</tr>
<tr>
  <td colspan="2"><label>{ts}End Date{/ts}</label></td>
</tr>
<tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="end_date" from='_low' to='_high'}
</tr>
{if $relationshipGroupTree}
  <tr>
    <td colspan="2">
      {include file="CRM/Custom/Form/Search.tpl" groupTree=$relationshipGroupTree showHideLinks=false}
    </td>
  </tr>
{/if}
