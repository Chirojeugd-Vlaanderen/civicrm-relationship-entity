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
{* Search form and results for Relationships *}
{assign var="showBlock" value="'searchForm'"}
{assign var="hideBlock" value="'searchForm_show'"}
<div class="crm-block crm-form-block crm-relationship-search-form-block">
  <div class="crm-accordion-wrapper crm-relationship_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
          {ts}Edit Search Criteria{/ts}
       </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">
        {strip}
          <table class="form-layout">
            <tr>
              <td class="font-size12pt" colspan="2">
                {$form.target_name.label}&nbsp;&nbsp;{$form.target_name.html|crmAddClass:'twenty'}&nbsp;&nbsp;&nbsp;{$form.buttons.html}
                <div class="description font-italic" style="font-size: 0.8em">
                  {ts}Complete OR partial contact name.{/ts}
                </div>
              </td>
          </tr>

          {include file="CRM/Relationship/Form/Search/Common.tpl"}
          <tr>
               <td colspan="2">{$form.buttons.html}</td>
            </tr>
            </table>
        {/strip}
      </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->
{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
<div class="crm-results-block crm-results-block-empty">
  {include file="CRM/Relationship/Form/Search/EmptyResults.tpl"}
</div>
{/if}

{if $rows}
    <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. *}
        {* This section handles form elements for action task select and submit *}
        <div class="crm-search-tasks crm-event-search-tasks">
          {include file="CRM/common/searchResultTasks.tpl" context="Relationship"}
        </div>

        {* This section displays the rows along and includes the paging controls *}
        <div id="relationshipSearch" class="crm-search-results">
          {include file="CRM/Relationship/Form/Selector.tpl" context="Search"}
        </div>
    {* END Actions/Results section *}
    </div>
{/if}

</div>
{/if}

