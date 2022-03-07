{assign var="allow_save" value=true}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" target="_self" name="manage_jobs_failed_form" id="manage_jobs_failed_form"
          data-ca-is-multiple-submit-allowed="true">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {if $jobs_failed}
            {capture name="jobs_failed_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive">
                        <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                        <tr>
                            <th class="left mobile-hide table__check-items-column" width="0%">
                                {include file="common/check_items.tpl"}

                                <input type="checkbox"
                                       class="bulkedit-toggler hide"
                                       data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                       data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                />
                            </th>
                            <th><a href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}">{__('id')}</a></th>
                            <th><a href="{"`$c_url`&sort_by=queue&sort_order=`$search.sort_order_rev`"|fn_url}">{__('queue.queue')}</a></th>
                            <th><a href="{"`$c_url`&sort_by=connection&sort_order=`$search.sort_order_rev`"|fn_url}">{__('queue.connection')}</a></th>
                            <th>{__('description')}</th>
                            <th class="right"><a href="{"`$c_url`&sort_by=failed_at&sort_order=`$search.sort_order_rev`"|fn_url}">{__('queue.failed_at')}</a></th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $jobs_failed as $job}
                            <tr class="cm-longtap-target"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$job.id}"
                            >
                                <td data-th="" class="left mobile-hide table__check-items-cell" width="0%">
                                    <input type="checkbox" name="job_ids[]" value="{$job.id}" class="cm-item hide"/>
                                </td>
                                <td data-th="{__('id')}">
                                    {$job.id}
                                </td>
                                <td data-th="{__('queue.queue')}">
                                    {$job.queue}
                                </td>
                                <td data-th="{__('queue.connection')}">
                                    {$job.connection}
                                </td>
                                <td data-th="{__('description')}">
                                    {$job.decoded_payload.displayName}
                                </td>
                                <td class="right" data-th="{__('queue.failed_at')}">
                                    {$job.failed_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                </td>
                            </tr>
                            <tr style="display: none" id="job_failed_{$job.id}_extra">
                                <td colspan="8">
                                    <pre>{$job.exception}</pre>
                                </td>
                            </tr>
                            <tr style="display: none" id="job_failed_{$job.id}_raw_payload">
                                <td colspan="8">
                                    <pre>{$job.payload}</pre>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_jobs_failed_form"
                object="jobs_failed"
                items=$smarty.capture.jobs_failed_table
                is_check_all_shown=true
            }

        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl" div_id=$smarty.request.content_id}

    </form>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="products:action_buttons"}
            {if $jobs_failed}
                <li>{btn type="list" text=__("queue.jobs_failed_prune") href="queue.jobs_failed_prune"}</li>
            {/if}
        {/hook}
    {/capture}

    {dropdown content=$smarty.capture.tools_list}

    {if $products}
        {include file="buttons/save.tpl" but_name="dispatch[products.m_update]" but_role="action" but_target_form="manage_products_form" but_meta="cm-submit"}
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title=__('queue.jobs_failed')
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
}
