<?php
/**
* Copyright © 2018 Crypto. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Crypto\Paymentgateway\Controller\Order;

use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $orderManagement;
    protected $resultJsonFactory;
	protected $resultFactory;
	
	/**
	* @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
	*/
    protected $invoiceSender;

    /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
   protected $scopeConfig;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /**
    * @var $merchantId
    **/
    protected $merchantId;

    /**
    * @var $merchantKey
    **/
    protected $merchantKey;

    /**
    * @var $coinId
    **/
    protected $coinId;

    /**
    * @var $type
    **/
    protected $type;

    /**
    * @var $orderTotal
    **/
    protected $orderTotal;

    /**
    * @var $_curlUrl
    **/
    protected $_curlUrl;

    /**
    * @var currencyCode
    **/
    protected $currencyCode;

    /**
    * @var $_storeManager
    **/
    protected $_storeManager;
    
    /**
    * @var $securityKey
    **/
    protected $securityKey;
	
	/**
    * @var $paidnotenoughStatus
    **/
    protected $paidnotenoughStatus;
	
	/**
    * @var $paidnotenoughStatus
    **/
    protected $paidStatus;
	
	/**
    * @var $failedStatus
    **/
    protected $failedStatus;

    /**
    * Merchant ID
    */
    const XML_PATH_MERCHANT_ID = 'payment/crypto_gateway/merchant_gateway_id';

    /**
    * Merchant COINTOPAY API Key
    */
    const XML_PATH_MERCHANT_KEY = 'payment/crypto_gateway/merchant_gateway_key';

    /**
    * Merchant COINTOPAY SECURITY Key
    */
    const XML_PATH_MERCHANT_SECURITY = 'payment/crypto_gateway/merchant_gateway_security';
	
	/**
    * Merchant COINTOPAY SECURITY Key
    */
    const XML_PATH_PAID_NOTENOUGH_ORDER_STATUS = 'payment/crypto_gateway/order_status_paid_notenough';
	
	/**
    * Merchant COINTOPAY SECURITY Key
    */
    const XML_PATH_PAID_ORDER_STATUS = 'payment/crypto_gateway/order_status_paid';
	
	/**
    * Merchant FAILED Order Status
    */
    const XML_PATH_ORDER_STATUS_FAILED = 'payment/crypto_gateway/order_status_failed';

    /**
    * API URL
    **/
    const COIN_TO_PAY_API = 'https://cointopay.com/MerchantAPI';

    /**
    * @var $response
    **/
    protected $response = [] ;

    /*
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Magento\Framework\Json\EncoderInterface $encoder
    * @param \Magento\Framework\HTTP\Client\Curl $curl
    * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    * @param \Magento\Framework\View\Result\PageFactory $pageFactory
    * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	* @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    */
    public function __construct (
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\Raw $resultJsonFactory,
		\Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        $this->_context = $context;
        $this->_jsonEncoder = $encoder;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_pageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
		$this->resultFactory = $resultFactory;
        $this->orderManagement = $orderManagement;
		$this->invoiceSender = $invoiceSender;
        parent::__construct($context);
    }

    public function execute()
    {
		$page = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        try {
            $customerReferenceNr = $this->getRequest()->getParam('CustomerReferenceNr');
            $status = $this->getRequest()->getParam('status');
            $ConfirmCode = $this->getRequest()->getParam('ConfirmCode');
            $SecurityCode = $this->getRequest()->getParam('SecurityCode');
            $notenough = $this->getRequest()->getParam('notenough');
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $this->securityKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_SECURITY, $storeScope);
			$this->paidnotenoughStatus = $this->scopeConfig->getValue(self::XML_PATH_PAID_NOTENOUGH_ORDER_STATUS, $storeScope);
			$this->paidStatus = $this->scopeConfig->getValue(self::XML_PATH_PAID_ORDER_STATUS, $storeScope);
			$this->failedStatus = $this->scopeConfig->getValue(self::XML_PATH_ORDER_STATUS_FAILED, $storeScope);
			/** @var Page $page */
            
            if ($this->securityKey == $SecurityCode) {
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$order = $objectManager->create('\Magento\Sales\Model\Order')
					->loadByIncrementId($customerReferenceNr);
				if (count($order->getData()) > 0) {
					if ($status == 'paid' && $notenough == 1) {
						$order->setState($this->paidnotenoughStatus)->setStatus($this->paidnotenoughStatus);
						$order->save();
						$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
							$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
							->setTemplate('Crypto_PaymentGateway::order_response.phtml')
							->setData('message','Not enough was received, please pay the remaining amount or contact support')
							->toHtml();
							$result->setContents($block);
							//return $result;
						   //$result->setData([$block]);
							return $result;
					} else if ($status == 'paid') {
						if ($order->canInvoice()) {
							$invoice = $order->prepareInvoice();
							$invoice->getOrder()->setIsInProcess(true);
							$invoice->register()->pay();
							$invoice->save();
						}

						$order->setState($this->paidStatus)->setStatus($this->paidStatus);
						$order->save();
						if ($order->canInvoice()) {
							$this->invoiceSender->send($invoice);
						}
						
					} else if ($status == 'failed') {
						if ($order->getStatus() == 'complete') {
							/** @var \Magento\Framework\Controller\Result\Json $result */
							$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
							$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
							->setTemplate('Crypto_PaymentGateway::order_response.phtml')
							->setData('message','Order cannot be cancel now, because it is completed now.')
							->toHtml();
							$result->setContents($block);
							return $result;
						} else {
							//$this->orderManagement->cancel($order->getId());
							$order->setState($this->failedStatus)->setStatus($this->failedStatus);
						    $order->save();
							$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
							$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
							->setTemplate('Crypto_PaymentGateway::order_response.phtml')
							->setData('message','Order successfully cancelled.')
							->toHtml();
							$result->setContents($block);
							//return $result;
						   //$result->setData([$block]);
							return $result;
							
						}
					} else {
						/** @var \Magento\Framework\Controller\Result\Json $result */
						$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
						$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
						->setTemplate('Crypto_PaymentGateway::order_response.phtml')
						->setData('message','Order status should have valid value.')
						->toHtml();
						$result->setContents($block);
						return $result;
					}
					/** @var \Magento\Framework\Controller\Result\Json $result */
					$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
					$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
					->setTemplate('Crypto_PaymentGateway::order_response.phtml')
					->setData('message','Order status successfully updated.')
					->toHtml();
					$result->setContents($block);
					return $result;
				} else {
					/** @var \Magento\Framework\Controller\Result\Json $result */
					$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
					$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
					->setTemplate('Crypto_PaymentGateway::order_response.phtml')
					->setData('message','Order status successfully updated.')
					->toHtml();
					$result->setContents($block);
					return $result;
				}
			} else {
				/** @var \Magento\Framework\Controller\Result\Json $result */
				$result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
				$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
				->setTemplate('Crypto_PaymentGateway::order_response.phtml')
				->setData('message','Order status successfully updated.')
				->toHtml();
				$result->setContents($block);
				return $result;
			}
        } catch (\Exception $e) {
            /** @var \Magento\Framework\Controller\Result\Json $result */
            $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
			$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
			->setTemplate('Crypto_PaymentGateway::order_response.phtml')
			->setData('message','General error:'.$e->getMessage())
			->toHtml();
			$result->setContents($block);
			return $result;
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
		$block = $page->getLayout()->createBlock('Crypto\PaymentGateway\Block\Index')
		->setTemplate('Crypto_PaymentGateway::order_response.phtml')
		->setData('message','Something went wrong. Try again later')
		->toHtml();
		$result->setContents($block);
		return $result;
    }
}
