<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Defensive Registration Status</h3>
    </div>
    <div class="panel-body">
        {if $error}
            <div class="alert alert-danger">
                {$error}
            </div>
        {else}
            <table class="table table-bordered">
                <tr>
                    <th width="40%">Protected Domain</th>
                    <td>{$name}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="label {if $status eq 'Active'}label-success{elseif $status eq 'Pending'}label-warning{else}label-default{/if}">
                            {$status}
                        </span>
                    </td>
                </tr>
                {if $handle}
                <tr>
                    <th>Registration Handle</th>
                    <td>{$handle}</td>
                </tr>
                {/if}
                {if $mark_handle}
                <tr>
                    <th>TMCH Mark Handle</th>
                    <td>{$mark_handle}</td>
                </tr>
                {/if}
                {if $created_date}
                <tr>
                    <th>Registration Date</th>
                    <td>{$created_date}</td>
                </tr>
                {/if}
                {if $expire_date}
                <tr>
                    <th>Expiry Date</th>
                    <td>{$expire_date}</td>
                </tr>
                {/if}
            </table>

            <div class="alert alert-info">
                <strong>Protection Active:</strong> Your defensive registration is actively protecting your brand
                by blocking third-party registration of similar domains.
            </div>
        {/if}
    </div>
</div>
