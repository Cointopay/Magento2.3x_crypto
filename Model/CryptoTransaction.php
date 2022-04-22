<?php 
namespace Crypto\PaymentGateway\Model;
 
 
class CryptoTransaction{
	
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_coreSession;
    protected $resultJsonFactory;
    protected $_objectManager;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_jsonDecoder;
	
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
    const XML_PATH_MERCHANT_KEY = 'payment/crypto_gateway/merchant_gateway_api_key';

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
    * @var \Magento\Framework\Registry
    */
    protected $_registry;
	
	/*
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
		* @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    */
    public function __construct (
	    \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\HTTP\Client\Curl $curl,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\Json\DecoderInterface $decoder
	) {
		$this->_context = $context;
        $this->scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_coreSession = $coreSession;
		$this->_curl = $curl;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->_jsonEncoder = $encoder;
        $this->_jsonDecoder = $decoder;
    }

	/**
	 * {@inheritdoc}
	 */
	public function getTransactions($id)
	{
		
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
        $this->merchantKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_KEY, $storeScope);
        $this->_curlUrl = 'https://app.cointopay.com/v2REAPI?Call=Transactiondetail&MerchantID='.$this->merchantId.'&APIKey='.$this->merchantKey.'&TransactionID='.$id.'&output=json';
        $this->_curl->get($this->_curlUrl);
        $response = $this->_jsonDecoder->decode($this->_curl->getBody());
				return [$response];
	}
	
}
