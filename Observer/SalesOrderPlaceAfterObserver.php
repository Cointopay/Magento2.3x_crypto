<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{

    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_coreSession;
    protected $_jsonDecoder;

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
    * Merchant COINTOPAY ORDER STATUS
    */
    const XML_PATH_ORDER_STATUS = 'payment/crypto_gateway/order_status';

    /**
    * API URL
    **/
    const COIN_TO_PAY_API = 'https://cointopay.com/MerchantAPI';

    /**
    * @var $response
    **/
    protected $response = [] ;

    protected $_request;
    protected $_historyFactory;
    protected $_orderFactory;

    protected $logger;

    /**
    * @var \Magento\Framework\Stdlib\CookieManagerInterface
    * @param \Magento\Framework\Json\EncoderInterface $encoder
    * @param \Magento\Framework\Json\DecoderInterface $decoder,
    * @param \Magento\Framework\HTTP\Client\Curl $curl
    * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    * @param \Magento\Framework\View\Result\PageFactory $pageFactory
    */
    protected $_cookieManager;
	
	/**
    * @var $paidStatus
    **/
    protected $orderStatus;


    public function __construct (
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\Json\DecoderInterface $decoder,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\ResponseFactory $_response,
        \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder    
	)
    {
        $this->logger = $logger;
        $this->_cookieManager = $cookieManager;
        $this->_jsonEncoder = $encoder;
        $this->_jsonDecoder = $decoder;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_pageFactory = $pageFactory;
        $this->_request = $request;
        $this->_historyFactory = $historyFactory;
        $this->_coreSession = $coreSession;
        $this->_orderFactory = $orderFactory;
		$this->_response = $_response;
        $this->_urlRewrite = $urlRewrite;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->urlFinder = $urlFinder;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $orderInstance Order */
		$order = $observer->getData('order');
		$additional_data = "";
		//$order = $observer->getEvent()->getOrder();
		$orderId = $order->getId();
		$this->_coreSession->start();
		//$this->coinId =  $this->_coreSession->getCoinId(); //$_SESSION['coin_id'];
		//$this->coinId = $_SESSION['coin_id'];
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();				
		//$orderObject = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
		$lastOrderId = $order->getIncrementId();
		$this->orderTotal = $order->getGrandTotal();
		$payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$store = $storeManager->getStore();
		$baseUrl = $store->getBaseUrl();
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		// // getting data from file
		// $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
		// $mediaPath=$fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
		if ($payment_method_code == 'crypto_gateway') {
			$additional_data = $order->getPayment()->getAdditionalInformation();
			//throw new \Magento\Framework\Exception\LocalizedException(__(var_dump($additional_data)));
			$this->coinId =  $additional_data['transaction_result'];
			$response = $this->sendCoins($lastOrderId);
			$orderresponse = $this->_jsonDecoder->decode($response);
			if(!isset($orderresponse['TransactionID'])){
				throw new \Magento\Framework\Exception\LocalizedException(__($response));
			}
			// $_SESSION['crypto_response'] = $response;
			$this->_coreSession->setCointopayresponse($response);
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
			$customerSession = $objectManager->get('Magento\Customer\Model\Session');
			$customerSession->setCoinresponse($response); //set value in customer session
			$order->setExtOrderId($orderresponse['TransactionID']);
            $this->orderStatus = $this->scopeConfig->getValue(self::XML_PATH_ORDER_STATUS, $storeScope);
			$order->setState($this->orderStatus)->setStatus($this->orderStatus);
			$order->save();
			$UrlRewriteCollection=$this->_urlRewrite->getCollection()->addFieldToFilter('request_path', 'checkout/onepage/success');
            $deleteItem = $UrlRewriteCollection->getFirstItem(); 
            if ($UrlRewriteCollection->getFirstItem()->getId()) {
                // target path does exist
                $filterData = [
                    UrlRewrite::REQUEST_PATH => 'checkout/onepage/success'
                ];
                $rewrite = $this->urlFinder->findOneByData($filterData);
                $urlRewriteModel = $this->_urlRewriteFactory->create();
                $deleteItem->delete();
                $customerSession->setCoinStoreId($rewrite->getStoreId()); 
                $customerSession->setCoinTargetPath($rewrite->getTargetPath());                
            }
		}
    }

    /**
    * @return json response
    **/
    private function sendCoins ($orderId = 0) {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
        $this->merchantKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_KEY, $storeScope);
        $this->securityKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_SECURITY, $storeScope);
        $this->currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $this->_curlUrl = 'https://cointopay.com/MerchantAPI?Checkout=true&MerchantID='.$this->merchantId.'&Amount='.$this->orderTotal.'&AltCoinID='.$this->coinId.'&CustomerReferenceNr='.$orderId.'&SecurityCode='.$this->securityKey.'&output=json&inputCurrency='.$this->currencyCode.'&transactionconfirmurl='.$baseUrl.'paymentcointopay/order/&transactionfailurl='.$baseUrl.'paymentcointopay/order/';
        $this->_curl->get($this->_curlUrl);
        $response = $this->_curl->getBody();
        return $response;
    }
}
