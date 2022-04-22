<?php
/**
* Copyright © 2018 Crypto. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Crypto\Paymentgateway\Controller\Index;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\App\ResponseInterface;


class Index extends \Magento\Framework\App\Action\Action
{
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_coreSession;
    protected $resultJsonFactory;
    protected $_objectManager;
    protected $_checkoutSession;
    protected $_orderFactory;
	
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
    * @var $paidStatus
    **/
    protected $paidStatus;

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
    const XML_PATH_PAID_ORDER_STATUS = 'payment/crypto_gateway/order_status_paid';

    /**
    * API URL
    **/
    const COIN_TO_PAY_API = 'https://cointopay.com/MerchantAPI';

    /**
    * @var $response
    **/
    protected $response = [] ;

    /**
    * @var \Magento\Framework\Registry
    */
    protected $_registry;

    /*
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Magento\Framework\Json\EncoderInterface $encoder
    * @param \Magento\Framework\HTTP\Client\Curl $curl
    * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    * @param \Magento\Framework\View\Result\PageFactory $pageFactory
    * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    * @param \Magento\Framework\Registry $registry
	* @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    */
    public function __construct (
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        $this->_context = $context;
        $this->_jsonEncoder = $encoder;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_pageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_coreSession = $coreSession;
        $this->_objectManager = $objectmanager;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_registry = $registry;
		$this->invoiceSender = $invoiceSender;
        parent::__construct($context);
    }

    public function execute()
    {
		
        if ($this->getRequest()->isXmlHttpRequest()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $this->coinId = $this->getRequest()->getParam('paymentaction');
            $this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
            $this->securityKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_SECURITY, $storeScope);
			$this->paidStatus = $this->scopeConfig->getValue(self::XML_PATH_PAID_ORDER_STATUS, $storeScope);
            $type = $this->getRequest()->getParam('type');
            $this->currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
            if ($type == 'status') {
                $response = $this->getStatus($this->coinId);
                if ($response == 'paid') {
                    $orderId = $this->getRealOrderId();
                    $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
					if ($order->canInvoice()) {
						$invoice = $order->prepareInvoice();
						$invoice->getOrder()->setIsInProcess(true);
						$invoice->register()->pay();
						$invoice->save();
					}
                    $order->setState($this->paidStatus)->setStatus($this->paidStatus);
                    $order->save();
					if (isset($invoice)) {
					   $this->invoiceSender->send($invoice);
					}
                }
                /** @var \Magento\Framework\Controller\Result\Json $result */
                $result = $this->resultJsonFactory->create();
                return $result->setData(['status' => $response]);
            } else {
                $this->_coreSession->start();
                $this->_coreSession->setCoinid($this->coinId);
                $isVerified = $this->verifyOrder();
                /** @var \Magento\Framework\Controller\Result\Json $result */
                $result = $this->resultJsonFactory->create();
                if ($isVerified == 'success') {
                    return $result->setData(['status' => 'success', 'coindid' => $this->coinId]);
                } else {
                    return $result->setData(['status' => 'error' , 'message' => $isVerified]);
                }
            }
        }
        return;
    }

    /**
    * @return json response
    **/
    private function payOrder() {
        $this->orderTotal = $this->getCartAmount();
        $this->_curlUrl = 'https://cointopay.com/MerchantAPI?Checkout=true&MerchantID='.$this->merchantId.'&Amount='.$this->orderTotal.'&AltCoinID='.$this->coinId.'&CustomerReferenceNr=buy%20something%20from%20me&SecurityCode='.$this->securityKey.'&output=json&inputCurrency='.$this->currencyCode;
        $this->_curl->get($this->_curlUrl);
        $response = $this->_curl->getBody();
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }

    /**
    * @return Total order amount from cart
    **/
    private function getCartAmount () {
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');   
        return $cart->getQuote()->getGrandTotal();
    }

    /**
    * @return string payment status
    **/
    private function getStatus ($TransactionID) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
        $this->_curlUrl = 'https://cointopay.com/CloneMasterTransaction?MerchantID='.$this->merchantId.'&TransactionID='.$TransactionID.'&output=json';
        $this->_curl->get($this->_curlUrl);
        $response = $this->_curl->getBody();
        $decoded = json_decode($response);
        return $decoded[1];
    }

    // verify that if order can be placed or not
    private function verifyOrder () {
        $this->orderTotal = $this->getCartAmount();
        $this->_curlUrl = 'https://cointopay.com/MerchantAPI?Checkout=true&MerchantID='.$this->merchantId.'&Amount='.$this->orderTotal.'&AltCoinID='.$this->coinId.'&CustomerReferenceNr=buy%20something%20from%20me&SecurityCode='.$this->securityKey.'&output=json&inputCurrency='.$this->currencyCode.'&testcheckout';
        $this->_curl->get($this->_curlUrl);
        $response = $this->_curl->getBody();
        if ($response == '"testcheckout success"') {
            return 'success';
        }
        return $response;
    }

    // get last order real ID
    public function getRealOrderId()
    {
        $lastorderId = $this->_checkoutSession->getLastOrderId();
        return $lastorderId;
    }
}