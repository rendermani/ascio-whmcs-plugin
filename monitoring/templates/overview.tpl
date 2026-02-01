<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Domain Monitoring Service</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6">
                <strong>Monitored Term:</strong>
            </div>
            <div class="col-sm-6">
                {$name}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <strong>Monitoring Tier:</strong>
            </div>
            <div class="col-sm-6">
                Tier {$tier}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <strong>Notification Frequency:</strong>
            </div>
            <div class="col-sm-6">
                {$frequency}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <strong>Status:</strong>
            </div>
            <div class="col-sm-6">
                <span class="label {if $status eq 'Active'}label-success{elseif $status eq 'Pending'}label-warning{else}label-default{/if}">
                    {$status}
                </span>
            </div>
        </div>
        {if $expiry}
        <div class="row">
            <div class="col-sm-6">
                <strong>Expiry Date:</strong>
            </div>
            <div class="col-sm-6">
                {$expiry|date_format:"%Y-%m-%d"}
            </div>
        </div>
        {/if}
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">About Domain Monitoring</h3>
    </div>
    <div class="panel-body">
        <p>Your domain monitoring service actively watches for:</p>
        <ul>
            <li>New domain registrations similar to your monitored term</li>
            <li>Potential trademark infringements</li>
            <li>Typosquatting attempts</li>
        </ul>
        <p>You will receive {$frequency|lower} reports via email with any matches found.</p>
    </div>
</div>
