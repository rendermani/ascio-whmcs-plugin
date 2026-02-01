<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Defensive Registration Service</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6">
                <strong>Protected Domain:</strong>
            </div>
            <div class="col-sm-6">
                {$name}
            </div>
        </div>
        {if $markHandle}
        <div class="row">
            <div class="col-sm-6">
                <strong>TMCH Mark:</strong>
            </div>
            <div class="col-sm-6">
                {$markHandle}
            </div>
        </div>
        {/if}
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
        {if $handle}
        <div class="row">
            <div class="col-sm-6">
                <strong>Registration Handle:</strong>
            </div>
            <div class="col-sm-6">
                {$handle}
            </div>
        </div>
        {/if}
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">About Defensive Registration</h3>
    </div>
    <div class="panel-body">
        <p>Your defensive registration (DPML) protects your brand by:</p>
        <ul>
            <li>Blocking third parties from registering similar domains</li>
            <li>Preventing typosquatting and brand abuse</li>
            <li>Providing legal protection across multiple TLDs</li>
        </ul>
        <p>This registration will automatically renew before expiration to maintain your protection.</p>
    </div>
</div>
