<?php 
namespace Crypto\PaymentGateway\Model;
 
 
class CryptoOrdersManagement {
	
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
    * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    */
    public function __construct (
	    \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\HTTP\Client\Curl $curl,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
		$this->_context = $context;
        $this->scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_coreSession = $coreSession;
		$this->_curl = $curl;
		$this->resultJsonFactory = $resultJsonFactory;
    }

	/**
	 * {@inheritdoc}
	 */
	public function getCoin($param)
	{
		
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$this->coinId = $param;
		$this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
		$this->securityKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_SECURITY, $storeScope);
		$this->paidStatus = $this->scopeConfig->getValue(self::XML_PATH_PAID_ORDER_STATUS, $storeScope);
		$this->currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
		
		return 'coindid ' . $this->coinId;
		
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
	
	/**
    * @return Total order amount from cart
    **/
    private function getCartAmount () {
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');   
        return $cart->getQuote()->getGrandTotal();
    }
}