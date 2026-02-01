<?php

namespace ascio\v3;

class OrderStatusType
{
    const __default = 'NotSet';
    const NotSet = 'NotSet';
    const Received = 'Received';
    const Validated = 'Validated';
    const Invalid = 'Invalid';
    const PendingDocumentation = 'PendingDocumentation';
    const PendingEndUserAction = 'PendingEndUserAction';
    const DocumentationReceived = 'DocumentationReceived';
    const DocumentationApproved = 'DocumentationApproved';
    const DocumentationNotApproved = 'DocumentationNotApproved';
    const PendingNicProcessing = 'PendingNicProcessing';
    const PendingNicDocumentApproval = 'PendingNicDocumentApproval';
    const PendingPostProcessing = 'PendingPostProcessing';
    const PendingInternalProcessing = 'PendingInternalProcessing';
    const Completed = 'Completed';
    const Failed = 'Failed';
    const AuthenticationFailed = 'AuthenticationFailed';


}
