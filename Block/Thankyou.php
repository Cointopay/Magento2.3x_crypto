<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Block;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Thankyou extends Template
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    protected $_coreSession;
	
	protected $_pageFactory;
    protected $_jsonEncoder;
    protected $resultJsonFactory;
    protected $_objectManager;
	
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
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\HTTP\Client\Curl $curl,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		BlockRepositoryInterface $blockRepository,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, (array) $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
		$this->scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_coreSession = $coreSession;
		$this->_curl = $curl;
		$this->resultJsonFactory = $resultJsonFactory;
    }

    public function getOrder()
    {
        $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
		if(empty($this->_order)){
			$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
			$orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection()->getLastItem();
			$orderId   =   $orderDatamodel->getId();
			$this->_order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
		}
		return  $this->_order;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }

    public function getCointopayHtml ()
    {
		
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
		
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $crypto_response = $customerSession->getCoinresponse();
        if (isset($crypto_response)) {
            $customerSession->unsCoinresponse();
            return json_decode($crypto_response);
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
		
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $crypto_response = $customerSession->getCoinresponse();
        if (isset($crypto_response)) {
            $customerSession->unsCoinresponse();
            return json_decode($crypto_response);
        }
		return false;
	}
}