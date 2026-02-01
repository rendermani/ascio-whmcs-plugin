<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Monitoring Service Status</h3>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tr>
                <th width="40%">Monitored Term</th>
                <td>{$name}</td>
            </tr>
            <tr>
                <th>Monitoring Tier</th>
                <td>Tier {$tier}{if $api_tier && $api_tier != $tier} <span class="text-muted">(API: Tier {$api_tier})</span>{/if}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="label {if $status eq 'Active'}label-success{elseif $status eq 'Pending'}label-warning{else}label-default{/if}">
                        {$status}
                    </span>
                    {if $api_status && $api_status != $status}
                    <span class="text-muted">(API: {$api_status})</span>
                    {/if}
                </td>
            </tr>
            <tr>
                <th>Notification Frequency</th>
                <td>{$frequency}</td>
            </tr>
            <tr>
                <th>Notification Email</th>
                <td>{$email|default:'Not configured'}</td>
            </tr>
            <tr>
                <th>Handle</th>
                <td><code>{$handle}</code></td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>{$order_id|default:'N/A'}</td>
            </tr>
            {if $expiry}
            <tr>
                <th>Expiry Date</th>
                <td>{$expiry|date_format:"%Y-%m-%d"}{if $api_expires && $api_expires != $expiry} <span class="text-muted">(API: {$api_expires})</span>{/if}</td>
            </tr>
            {/if}
            {if $api_created}
            <tr>
                <th>Created Date</th>
                <td>{$api_created}</td>
            </tr>
            {/if}
        </table>
        {if $api_error}
        <div class="alert alert-warning">
            <strong>Note:</strong> Could not refresh data from API: {$api_error}
        </div>
        {/if}
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">Tier Information</h3>
    </div>
    <div class="panel-body">
        <p>Your monitoring service is configured at <strong>Tier {$tier}</strong>.</p>
        <ul>
            <li><strong>Tier 1:</strong> Basic monitoring - exact matches only</li>
            <li><strong>Tier 2:</strong> Extended monitoring - common variations</li>
            <li><strong>Tier 3:</strong> Enhanced monitoring - typosquatting detection</li>
            <li><strong>Tier 4:</strong> Advanced monitoring - phonetic variations</li>
            <li><strong>Tier 5:</strong> Comprehensive monitoring - all detection methods</li>
        </ul>
    </div>
</div>
