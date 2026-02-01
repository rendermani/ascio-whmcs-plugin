<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Trademark Clearinghouse (TMCH) Service</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6">
                <strong>Mark Name:</strong>
            </div>
            <div class="col-sm-6">
                {$markName}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <strong>Mark Type:</strong>
            </div>
            <div class="col-sm-6">
                {$markType}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <strong>Service Type:</strong>
            </div>
            <div class="col-sm-6">
                {$serviceType}
            </div>
        </div>
        {if $markId}
        <div class="row">
            <div class="col-sm-6">
                <strong>TMCH Mark ID:</strong>
            </div>
            <div class="col-sm-6">
                {$markId}
            </div>
        </div>
        {/if}
        <div class="row">
            <div class="col-sm-6">
                <strong>Status:</strong>
            </div>
            <div class="col-sm-6">
                <span class="label {if $status eq 'Active'}label-success{elseif $status eq 'Pending'}label-warning{elseif $status eq 'Pending_End_User_Action'}label-info{else}label-default{/if}">
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

{if $needsDocuments}
<div class="panel panel-warning">
    <div class="panel-heading">
        <h3 class="panel-title">Document Upload Required</h3>
    </div>
    <div class="panel-body">
        <p>Your trademark registration requires supporting documentation. Please upload the required documents below.</p>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="doc_type">Document Type</label>
                <select name="doc_type" id="doc_type" class="form-control">
                    <option value="TrademarkCopy">Trademark Certificate Copy</option>
                    <option value="ProofOfUse">Proof of Use</option>
                    <option value="Declaration">Signed Declaration</option>
                    <option value="Other">Other Supporting Document</option>
                </select>
            </div>
            <div class="form-group">
                <label for="document">Select File</label>
                <input type="file" name="document" id="document" class="form-control" required>
                <p class="help-block">Accepted formats: PDF, JPG, PNG. Maximum size: 5MB.</p>
            </div>
            <button type="submit" name="upload_document" class="btn btn-primary">Upload Document</button>
        </form>
    </div>
</div>
{/if}

<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">About TMCH</h3>
    </div>
    <div class="panel-body">
        <p>The Trademark Clearinghouse (TMCH) is a global repository of verified trademarks that provides:</p>
        <ul>
            {if $serviceType eq 'Sunrise'}
            <li><strong>Sunrise Period Access:</strong> Register domains before general availability during new TLD launches</li>
            {/if}
            {if $serviceType eq 'Claims'}
            <li><strong>Claims Notifications:</strong> Receive alerts when someone tries to register a domain matching your mark</li>
            {/if}
            <li><strong>Trademark Protection:</strong> Global validation and protection of your trademark</li>
            <li><strong>Priority Rights:</strong> Establish priority during domain disputes</li>
        </ul>
    </div>
</div>
