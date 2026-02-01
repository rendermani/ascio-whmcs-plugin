<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Monitoring Alerts</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6">
                <strong>Monitored Term:</strong> {$name}
            </div>
            <div class="col-sm-6">
                <strong>Status:</strong>
                <span class="label {if $status eq 'Active'}label-success{elseif $status eq 'Pending'}label-warning{else}label-default{/if}">
                    {$status}
                </span>
            </div>
        </div>
        <div class="row" style="margin-top: 10px;">
            <div class="col-sm-6">
                <strong>Monitoring Tier:</strong> Tier {$tier}
            </div>
            <div class="col-sm-6">
                <strong>Notification Frequency:</strong> {$frequency}
            </div>
        </div>
        {if $expires}
        <div class="row" style="margin-top: 10px;">
            <div class="col-sm-6">
                <strong>Expiry Date:</strong> {$expires}
            </div>
            <div class="col-sm-6">
                <strong>Notification Email:</strong> {$email|default:'Not set'}
            </div>
        </div>
        {/if}
    </div>
</div>

{if $message}
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> {$message}
</div>
{/if}

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Recent Alerts</h3>
    </div>
    <div class="panel-body">
        {if $alerts && count($alerts) > 0}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Domain</th>
                    <th>Type</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$alerts item=alert}
                <tr>
                    <td>{$alert.date}</td>
                    <td>{$alert.domain}</td>
                    <td>{$alert.type}</td>
                    <td>{$alert.details}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <div class="text-center text-muted" style="padding: 30px;">
            <i class="fas fa-bell-slash fa-3x" style="margin-bottom: 15px;"></i>
            <p>No alerts to display at this time.</p>
            <p>Alerts are sent to your registered email address at your configured notification frequency.</p>
        </div>
        {/if}
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">About Monitoring Alerts</h3>
    </div>
    <div class="panel-body">
        <p>Your domain monitoring service watches for:</p>
        <ul>
            <li>New domain registrations similar to your monitored term</li>
            <li>Potential trademark infringements</li>
            <li>Typosquatting attempts and variations</li>
            <li>Homograph attacks using similar-looking characters</li>
        </ul>
        <p>
            Alerts are delivered via email to <strong>{$email|default:'your registered email'}</strong>
            on a <strong>{$frequency|lower}</strong> basis.
        </p>
    </div>
</div>
