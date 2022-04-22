<?php
namespace Crypto\PaymentGateway\Block;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

class CtpInfo extends Template
{
    /*public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }*/

    protected $_orderFactory;
	
	protected $_pageFactory;
    protected $_jsonEncoder;
    protected $resultJsonFactory;
    protected $_objectManager;
	protected $customerSession;
	
	/** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection    */
	protected $_paymentCollectionFactory;
	 /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
	protected $_orderCollectionFactory;
	/** @var \Magento\Sales\Model\ResourceModel\Order\Collection */
	protected $orders;
	
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
    * @var $merchantKey
    **/
    protected $merchantKey;
	
	/**
    * @var $merchantId
    **/
    protected $merchantId;

    /**
    * @var $_curlUrl
    **/
    protected $_curlUrl;
	
	/**
    * @var $transactionId
    **/
    protected $transactionId;

    /**
    * Merchant COINTOPAY API Key
    */
    const XML_PATH_MERCHANT_KEY = 'payment/crypto_gateway/merchant_gateway_api_key';
	
	/**
    * Merchant ID
    */
    const XML_PATH_MERCHANT_ID = 'payment/crypto_gateway/merchant_gateway_id';

    /**
    * @var $response
    **/
    protected $response = [] ;
    
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\HTTP\Client\Curl $curl,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory, 
		\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
		\Magento\Customer\Model\Session $customerSession,
		array $data = []

    ) {
        parent::__construct($context, (array) $registry, $data);
        $this->_orderFactory = $orderFactory;
		$this->scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_curl = $curl;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_paymentCollectionFactory = $paymentCollectionFactory;
		$this->customerSession = $customerSession;
    }

    public function getOrder()
    {
	   //get values of current page
        $page=($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
		
		//get values of current limit
        $pageSize=($this->getRequest()->getParam('limit'))? $this->getRequest()->getParam('limit') : 10;

        $customerEmailId = 'goshila.sadaf@devprovider.com';
        $orderCollection = $this->_orderCollectionFactory->create($this->getCustomerId());
        
		$orderCollection->getSelect()->join(
			["sop" => "sales_order_payment"],
			'main_table.entity_id = sop.parent_id',
			array('method')
		)->where('sop.method = ?','crypto_gateway');
		
		$orderCollection->setOrder('entity_id','DESC');
        $orderCollection->setPageSize($pageSize);
        $orderCollection->setCurPage($page);
		return  $orderCollection;
    }


    public function getCustomerId()
    {
       return $this->customerSession->getCustomer()->getId();
    }

    public function getCointopayHtml ()
    {

		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$store = $storeManager->getStore();
		$baseUrl = $store->getBaseUrl();
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$orderObj = $this->getOrder();
		$payment_method_code = $orderObj->getPayment()->getMethodInstance()->getCode();
		if ($payment_method_code == 'crypto_gateway') {
			if(null !== $orderObj->getExtOrderId()){
				$this->transactionId = $orderObj->getExtOrderId();
				$this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
				$this->merchantKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_KEY, $storeScope);
				$this->_curlUrl = 'https://app.cointopay.com/v2REAPI?Call=Transactiondetail&MerchantID='.$this->merchantId.'&APIKey='.$this->merchantKey.'&TransactionID='.$this->transactionId.'&output=json';
				$this->_curl->get($this->_curlUrl);
				$response = $this->_curl->getBody();
				return json_decode($response);
			}
		}
		return false;
		
    }
    
    /**
     * Returns value view
     *
     * @return string | URL
     */
    public function getCoinsPaymentUrl()
    {
        return $this->getUrl("paymentcrypto");
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function getTransactions()
	{
		
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$orderObj = $this->getOrder();
		if(null !== $orderObj->getExtOrderId()){
		$this->transactionId = $orderObj->getExtOrderId();
		$this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
        $this->merchantKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_KEY, $storeScope);
        $this->_curlUrl = 'https://app.cointopay.com/v2REAPI?Call=Transactiondetail&MerchantID='.$this->merchantId.'&APIKey='.$this->merchantKey.'&TransactionID='.$this->transactionId.'&output=json';
        $this->_curl->get($this->_curlUrl);
        $response = $this->_curl->getBody();
        return json_decode($response);
		}
		return false;
	}
	
	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		$this->pageConfig->getTitle()->set(__('Order'));


		if ($this->getOrder()) {
			$pager = $this->getLayout()->createBlock(
				'Magento\Theme\Block\Html\Pager',
				'crypto.order.pager'
			)->setAvailableLimit(array(5=>5,10=>10,15=>15))->setShowPerPage(true)->setCollection(
				$this->getOrder()
			);
			$this->setChild('pager', $pager);

			$this->getOrder()->load();
		}
		return $this;
	}
	public function getPagerHtml()
	{
		return $this->getChildHtml('pager');
	}
}