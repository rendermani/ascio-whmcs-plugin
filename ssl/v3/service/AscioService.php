<?php

namespace ascio\v3;

class AscioService extends \SoapClient
{

    /**
     * @var array $classmap The defined aws-classes
     */
    private static $classmap = array (
      'CreateOrder' => 'ascio\\v3\\CreateOrder',
      'CreateOrderResponse' => 'ascio\\v3\\CreateOrderResponse',
      'ValidateOrder' => 'ascio\\v3\\ValidateOrder',
      'ValidateOrderResponse' => 'ascio\\v3\\ValidateOrderResponse',
      'GetOrder' => 'ascio\\v3\\GetOrder',
      'GetOrderResponse' => 'ascio\\v3\\GetOrderResponse',
      'GetOrders' => 'ascio\\v3\\GetOrders',
      'GetOrdersResponse' => 'ascio\\v3\\GetOrdersResponse',
      'GetMark' => 'ascio\\v3\\GetMark',
      'GetMarkResponse' => 'ascio\\v3\\GetMarkResponse',
      'GetDefensive' => 'ascio\\v3\\GetDefensive',
      'GetDefensiveResponse' => 'ascio\\v3\\GetDefensiveResponse',
      'GetNameWatch' => 'ascio\\v3\\GetNameWatch',
      'GetNameWatchResponse' => 'ascio\\v3\\GetNameWatchResponse',
      'GetSslCertificate' => 'ascio\\v3\\GetSslCertificate',
      'GetSslCertificateResponse' => 'ascio\\v3\\GetSslCertificateResponse',
      'GetAutoInstallSsl' => 'ascio\\v3\\GetAutoInstallSsl',
      'GetAutoInstallSslResponse' => 'ascio\\v3\\GetAutoInstallSslResponse',
      'GetMessages' => 'ascio\\v3\\GetMessages',
      'GetMessagesResponse' => 'ascio\\v3\\GetMessagesResponse',
      'PollQueue' => 'ascio\\v3\\PollQueue',
      'PollQueueResponse' => 'ascio\\v3\\PollQueueResponse',
      'AckQueueMessage' => 'ascio\\v3\\AckQueueMessage',
      'AckQueueMessageResponse' => 'ascio\\v3\\AckQueueMessageResponse',
      'GetQueueMessage' => 'ascio\\v3\\GetQueueMessage',
      'GetQueueMessageResponse' => 'ascio\\v3\\GetQueueMessageResponse',
      'UploadDocumentation' => 'ascio\\v3\\UploadDocumentation',
      'UploadDocumentationResponse' => 'ascio\\v3\\UploadDocumentationResponse',
      'UploadMessage' => 'ascio\\v3\\UploadMessage',
      'UploadMessageResponse' => 'ascio\\v3\\UploadMessageResponse',
      'AbstractOrderRequest' => 'ascio\\v3\\AbstractOrderRequest',
      'MarkOrderRequest' => 'ascio\\v3\\MarkOrderRequest',
      'AbstractMark' => 'ascio\\v3\\AbstractMark',
      'Registrant' => 'ascio\\v3\\Registrant',
      'Contact' => 'ascio\\v3\\Contact',
      'Extensions' => 'ascio\\v3\\Extensions',
      'KeyValue' => 'ascio\\v3\\KeyValue',
      'AutoInstallSslOrderRequest' => 'ascio\\v3\\AutoInstallSslOrderRequest',
      'AutoInstallSsl' => 'ascio\\v3\\AutoInstallSsl',
      'SslCertificateOrderRequest' => 'ascio\\v3\\SslCertificateOrderRequest',
      'SslCertificate' => 'ascio\\v3\\SslCertificate',
      'TreatyOrStatuteMark' => 'ascio\\v3\\TreatyOrStatuteMark',
      'Trademark' => 'ascio\\v3\\Trademark',
      'CourtValidatedMark' => 'ascio\\v3\\CourtValidatedMark',
      'SecurityHeaderDetails' => 'ascio\\v3\\SecurityHeaderDetails',
      'ArrayOfMarkOrderDocument' => 'ascio\\v3\\ArrayOfMarkOrderDocument',
      'MarkOrderDocument' => 'ascio\\v3\\MarkOrderDocument',
      'Attachment' => 'ascio\\v3\\Attachment',
      'NameWatchOrderRequest' => 'ascio\\v3\\NameWatchOrderRequest',
      'NameWatch' => 'ascio\\v3\\NameWatch',
      'DefensiveOrderRequest' => 'ascio\\v3\\DefensiveOrderRequest',
      'Defensive' => 'ascio\\v3\\Defensive',
      'AbstractResponse' => 'ascio\\v3\\AbstractResponse',
      'OrderInfo' => 'ascio\\v3\\OrderInfo',
      'GetOrderRequest' => 'ascio\\v3\\GetOrderRequest',
      'GetOrdersRequest' => 'ascio\\v3\\GetOrdersRequest',
      'ArrayOfOrderStatusType' => 'ascio\\v3\\ArrayOfOrderStatusType',
      'ArrayOfOrderType' => 'ascio\\v3\\ArrayOfOrderType',
      'ArrayOfObjectType' => 'ascio\\v3\\ArrayOfObjectType',
      'PagingInfo' => 'ascio\\v3\\PagingInfo',
      'ArrayOfOrderInfo' => 'ascio\\v3\\ArrayOfOrderInfo',
      'GetMarkRequest' => 'ascio\\v3\\GetMarkRequest',
      'MarkInfo' => 'ascio\\v3\\MarkInfo',
      'GetDefensiveRequest' => 'ascio\\v3\\GetDefensiveRequest',
      'DefensiveInfo' => 'ascio\\v3\\DefensiveInfo',
      'GetNameWatchRequest' => 'ascio\\v3\\GetNameWatchRequest',
      'NameWatchInfo' => 'ascio\\v3\\NameWatchInfo',
      'GetSslCertificateRequest' => 'ascio\\v3\\GetSslCertificateRequest',
      'SslCertificateInfo' => 'ascio\\v3\\SslCertificateInfo',
      'GetAutoInstallSslRequest' => 'ascio\\v3\\GetAutoInstallSslRequest',
      'AutoInstallSslInfo' => 'ascio\\v3\\AutoInstallSslInfo',
      'GetMessagesRequest' => 'ascio\\v3\\GetMessagesRequest',
      'ArrayOfMessage' => 'ascio\\v3\\ArrayOfMessage',
      'Message' => 'ascio\\v3\\Message',
      'ArrayOfAttachment' => 'ascio\\v3\\ArrayOfAttachment',
      'PollQueueRequest' => 'ascio\\v3\\PollQueueRequest',
      'QueueMessage' => 'ascio\\v3\\QueueMessage',
      'ArrayOfErrorCode' => 'ascio\\v3\\ArrayOfErrorCode',
      'ErrorCode' => 'ascio\\v3\\ErrorCode',
      'AckQueueMessageRequest' => 'ascio\\v3\\AckQueueMessageRequest',
      'GetQueueMessageRequest' => 'ascio\\v3\\GetQueueMessageRequest',
      'UploadDocumentationRequest' => 'ascio\\v3\\UploadDocumentationRequest',
      'UploadMessageRequest' => 'ascio\\v3\\UploadMessageRequest',
      'ArrayOfstring' => 'ascio\\v3\\ArrayOfstring',
      'ArrayOfint' => 'ascio\\v3\\ArrayOfint',
    );

    /**
     * @param array $options A array of config values
     * @param string $wsdl The wsdl file to use
     */
    public function __construct(array $options = array(), $wsdl = 'https://aws.ascio.com/v3/aws.wsdl')
    {
      foreach (self::$classmap as $key => $value) {
        if (!isset($options['classmap'][$key])) {
          $options['classmap'][$key] = $value;
        }
      }
      $options = array_merge(array (
      'features' => 1,
    ), $options);
      parent::__construct($wsdl, $options);
    }

    /**
     * @param CreateOrder $parameters
     * @return CreateOrderResponse
     */
    public function CreateOrder(CreateOrder $parameters)
    {
      return $this->__soapCall('CreateOrder', array($parameters));
    }

    /**
     * @param ValidateOrder $parameters
     * @return ValidateOrderResponse
     */
    public function ValidateOrder(ValidateOrder $parameters)
    {
      return $this->__soapCall('ValidateOrder', array($parameters));
    }

    /**
     * @param GetOrder $parameters
     * @return GetOrderResponse
     */
    public function GetOrder(GetOrder $parameters)
    {
      return $this->__soapCall('GetOrder', array($parameters));
    }

    /**
     * @param GetOrders $parameters
     * @return GetOrdersResponse
     */
    public function GetOrders(GetOrders $parameters)
    {
      return $this->__soapCall('GetOrders', array($parameters));
    }

    /**
     * @param GetMark $parameters
     * @return GetMarkResponse
     */
    public function GetMark(GetMark $parameters)
    {
      return $this->__soapCall('GetMark', array($parameters));
    }

    /**
     * @param GetDefensive $parameters
     * @return GetDefensiveResponse
     */
    public function GetDefensive(GetDefensive $parameters)
    {
      return $this->__soapCall('GetDefensive', array($parameters));
    }

    /**
     * @param GetNameWatch $parameters
     * @return GetNameWatchResponse
     */
    public function GetNameWatch(GetNameWatch $parameters)
    {
      return $this->__soapCall('GetNameWatch', array($parameters));
    }

    /**
     * @param GetSslCertificate $parameters
     * @return GetSslCertificateResponse
     */
    public function GetSslCertificate(GetSslCertificate $parameters)
    {
      return $this->__soapCall('GetSslCertificate', array($parameters));
    }

    /**
     * @param GetAutoInstallSsl $parameters
     * @return GetAutoInstallSslResponse
     */
    public function GetAutoInstallSsl(GetAutoInstallSsl $parameters)
    {
      return $this->__soapCall('GetAutoInstallSsl', array($parameters));
    }

    /**
     * @param GetMessages $parameters
     * @return GetMessagesResponse
     */
    public function GetMessages(GetMessages $parameters)
    {
      return $this->__soapCall('GetMessages', array($parameters));
    }

    /**
     * @param PollQueue $parameters
     * @return PollQueueResponse
     */
    public function PollQueue(PollQueue $parameters)
    {
      return $this->__soapCall('PollQueue', array($parameters));
    }

    /**
     * @param AckQueueMessage $parameters
     * @return AckQueueMessageResponse
     */
    public function AckQueueMessage(AckQueueMessage $parameters)
    {
      return $this->__soapCall('AckQueueMessage', array($parameters));
    }

    /**
     * @param GetQueueMessage $parameters
     * @return GetQueueMessageResponse
     */
    public function GetQueueMessage(GetQueueMessage $parameters)
    {
      return $this->__soapCall('GetQueueMessage', array($parameters));
    }

    /**
     * @param UploadDocumentation $parameters
     * @return UploadDocumentationResponse
     */
    public function UploadDocumentation(UploadDocumentation $parameters)
    {
      return $this->__soapCall('UploadDocumentation', array($parameters));
    }

    /**
     * @param UploadMessage $parameters
     * @return UploadMessageResponse
     */
    public function UploadMessage(UploadMessage $parameters)
    {
      return $this->__soapCall('UploadMessage', array($parameters));
    }

}
