<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Protected Domains</h3>
    </div>
    <div class="panel-body">
        {if $error}
            <div class="alert alert-danger">
                {$error}
            </div>
        {else}
            <div class="row">
                <div class="col-md-6">
                    <h4>Registration Details</h4>
                    <table class="table table-condensed">
                        <tr>
                            <th>Protected Term</th>
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
                        {if $mark_handle}
                        <tr>
                            <th>TMCH Mark</th>
                            <td>{$mark_handle}</td>
                        </tr>
                        {/if}
                    </table>
                </div>
                <div class="col-md-6">
                    <h4>Sample Blocked Domains</h4>
                    <p class="text-muted">Your defensive registration blocks registrations including:</p>
                    <ul class="list-group">
                        {foreach from=$blocked_patterns item=pattern}
                            <li class="list-group-item">
                                {if $pattern|strpos:'...' !== false}
                                    <em class="text-muted">{$pattern}</em>
                                {else}
                                    <span class="glyphicon glyphicon-ban-circle text-danger"></span>
                                    {$pattern}
                                {/if}
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>How DPML Protection Works:</strong>
                <ul>
                    <li>Third parties cannot register domains matching your protected term</li>
                    <li>Protection extends across multiple top-level domains (TLDs)</li>
                    <li>Typosquatting and brand abuse attempts are automatically blocked</li>
                    <li>Your trademark is protected without needing to register each domain individually</li>
                </ul>
            </div>
        {/if}
    </div>
</div>
