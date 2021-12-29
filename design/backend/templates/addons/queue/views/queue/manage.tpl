{assign var="allow_save" value=true}

{capture name="mainbox"}
    {include file="common/pagination.tpl"}

    {if $queue_messages}
        <div class="table-responsive-wrapper">
            <table class="table table--relative table-responsive">
                <thead>
                <tr>
                    <th>Queue message</th>
                    <th>Inserted on</th>
                    <th>Read on</th>
                    <th>Attempts</th>
                </tr>
                </thead>
                <tbody>
                {foreach $queue_messages as $message}
                    <tr>
                        <td data-th="Queue message">
                            # {$message.id} {$message.queue_id}@{$message.timeout}<br>
                            <bdi>{$message.body}</bdi>
                        </td>
                        <td data-th="Inserted on">
                            {$message.inserted_on|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </td>
                        <td data-th="Read on">
                            {$message.read_on|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </td>
                        <td data-th="Attempts">
                            {$message.attempts}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

    {include file="common/pagination.tpl"}
{/capture}

{$title = "Queue messages"}

{include file="common/mainbox.tpl" title=$title content=$smarty.capture.mainbox buttons=$smarty.capture.buttons}
