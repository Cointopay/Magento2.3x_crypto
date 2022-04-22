<?php
/**
* Copyright © 2018 Crypto. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Crypto\Paymentgateway\Controller\Ctpinfo;

use Magento\Framework\App\ResponseInterface;


class Crypto extends \Magento\Framework\App\Action\Action
{
    protected $_context;
    protected $_jsonEncoder;
    protected $resultJsonFactory;
	protected $_resultOutput;
	protected $_requestRepo;
	protected $_assetRepo;

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
    const XML_PATH_MERCHANT_KEY = 'payment/crypto_gateway/merchant_gateway_api_key';

    /**
    * Merchant COINTOPAY SECURITY Key
    */
    const XML_PATH_MERCHANT_SECURITY = 'payment/crypto_gateway/merchant_gateway_security';

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
    * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	* @param \Magento\Framework\View\Asset\Repository
	* @param \Magento\Framework\App\RequestInterface
    */
    public function __construct (
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\View\Asset\Repository $assetRepo,
		\Magento\Framework\App\RequestInterface $requestRepo
    ) {
        $this->_context = $context;
        $this->_jsonEncoder = $encoder;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
		$this->_requestRepo = $requestRepo;
        $this->_assetRepo = $assetRepo;
        parent::__construct($context);
    }

    public function execute()
    {
		
        if ($this->getRequest()->isXmlHttpRequest()) {
			if ($this->getRequest()->getParam('selected_transaction_id')) {
				$objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); 
				$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
				$store = $storeManager->getStore();
				$baseUrl = $store->getBaseUrl();
				$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
				$transactionId = $this->getRequest()->getParam('selected_transaction_id');
				$this->merchantId = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_ID, $storeScope);
				$this->merchantKey = $this->scopeConfig->getValue(self::XML_PATH_MERCHANT_KEY, $storeScope);
				$this->_curlUrl = 'https://app.cointopay.com/v2REAPI?Call=Transactiondetail&MerchantID='.$this->merchantId.'&APIKey='.$this->merchantKey.'&TransactionID='.$transactionId.'&output=json';
				$this->_curl->get($this->_curlUrl);
				$response_body = $this->_curl->getBody();
				$result = json_decode($response_body);
				$result_ctp = $this->resultJsonFactory->create();
				if ($result) {
					if ($result->status_code == 200) {
						$response = $result->data;
						$this->_resultOutput = '';
						$params = array('_secure' => $this->_requestRepo->isSecure());
						$getUrl = $objectManager->get('\Magento\Framework\View\Element\Template');
					
						$this->_resultOutput .= '<div class="popup-overlay crypto active">
						<div class="popup-content crypto active">    
							<img src="'.$this->_assetRepo->getUrlWithParams('Crypto_PaymentGateway::images/crypto.gif', $params).'" />
							<p class="description" style="text-align:center !important;"><strong>Please make the payment and wait for the success confirmation page.</strong></p>
							<h1> PAYMENT DETAILS </h1>
							<div class="crypto_details_main">
							<div class="crypto_details_qrcode">
							<img src="data:image/png;base64,'. base64_encode(file_get_contents($response->QRCodeURL)) .'" alt="Crypto Transaction details are in progress please wait." title="QR Scan Cointopay" class="ctpQRcode" width="" />
							<img src="data:image/png;base64,'. base64_encode(file_get_contents('https://chart.googleapis.com/chart?chs=300&cht=qr&chl='.$response->coinAddress)) .'" alt="ctpCoinAdress" class="ctpCoinAdress" title="coinAddress" style="display:none;" width="" />
							</div>
							<div class="crypto_details">
								<p class="remaining_amount"><strong>Amount:</strong><br>
								   '.  $response->Amount.' ' .'
									'. strtoupper($response->CoinName).' ' .'
									<img src="data:image/png;base64,'. base64_encode(file_get_contents('https://s3-eu-west-1.amazonaws.com/cointopay/img/'.$response->CoinName.'_dash2.png')) .'" style="width:20px;" />
								</p>';
								if (property_exists($response, 'Tag')) {
									if (!empty($response->Tag)) {
										$this->_resultOutput .= '<p class="description"><strong>Memo/Tag: </strong> '.$response->Tag.' </p>';
									}
								}
								$this->_resultOutput .= '<p class="address"><strong>Address: </strong> <br> <input type="text" value="'. $response->coinAddress .'"> </p>
								<p class="description"><button class="btn btn-success btnCrypto mb-2">CRYPTO LINK</button></p>
								<p class="time"><strong>Expiry: </strong> <span id="expire_time">'. date("m/d/Y h:i:s T",strtotime("+".$response->ExpiryTime." minutes")) .'</span></p>
								<p class="trxid"><strong>Transaction ID: </strong> '. $response->TransactionID .'</p>
								<p class="description">Make sure to send enough to cover  any coin transaction fees!</p>
								<p class="description">Send an equal amount or more.</p>
								<input type="hidden" id="crypto_trid" value="'. $response->TransactionID .'" >
							</div>
							</div>
						</div>
						</div>
						<script type="text/javascript">
							require(
								[\'jquery\'],
								function($) {
									$(function() {
										interval = setInterval(function() {
											if ($("#crypto_trid").length) {
												selected_coin = $("#crypto_trid").val();
												$.ajax ({
													url: "'.$getUrl->getUrl("paymentcrypto") .'",
													//showLoader: true,
													data: {paymentaction:selected_coin, type:\'status\'},
													type: "POST",
													success: function(result) {
														if (result.status == \'paid\') {
															$(\'.popup-content.crypto\').html(\'<h3>Thank you, your payment is received!</h3>\');
														} else if (result.status == \'expired\') {
															$(\'.popup-content.crypto\').html(\'<h3>Payment time expired!</h3>\');
														}
														 else if (result.status == \'underpaid\') {
															$(\'.popup-content.crypto\').html(\'<h3>You have underpaid, please pay the remaining amount or contact support!</h3>\');
														}
													}
												});
											}
										}, 10000);
										
										// count down time left
										var d1 = new Date (),
										d2 = new Date ( d1 );
										d2.setMinutes ( d1.getMinutes() + '. $response->ExpiryTime .' );
										var countDownDate = d2.getTime();

										// Update the count down every 1 second
										var x = setInterval(function() {
											if ($(\'#expire_time\').length) {
												// Get todays date and time
												var now = new Date().getTime();
												
												// Find the distance between now an the count down date
												var distance = countDownDate - now;
												
												// Time calculations for days, hours, minutes and seconds
												var days = Math.floor(distance / (1000 * 60 * 60 * 24));
												var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
												var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
												var seconds = Math.floor((distance % (1000 * 60)) / 1000);
												
												// Output the result in an element with id="expire_time"
												document.getElementById("expire_time").innerHTML = days + "d " + hours + "h "
												+ minutes + "m " + seconds + "s ";
												
												// If the count down is over, write some text 
												if (distance < 0) {
													clearInterval(x);
													document.getElementById("expire_time").innerHTML = "EXPIRED";
												}
											}
										}, 1000);
										if ($(\'.btnCrypto\').length) {
											$(\'.btnCrypto\').click(function(){
												if ($(this).text() == \'CRYPTO LINK\') {
													$(this).text(\'CRYPTO ADDRESS\');
													$(\'.ctpQRcode\').hide();
													$(\'.ctpCoinAdress\').show();
												} else if ($(this).text() == \'CRYPTO ADDRESS\') {
													$(this).text(\'CRYPTO LINK\');
													$(\'.ctpCoinAdress\').hide();
													$(\'.ctpQRcode\').show();
												}
												
											});
										}
									});
								});
						</script>';
						return $result_ctp->setData(["ctpData" => $this->_resultOutput]);
					} else {
						return $result_ctp->setData(["ctpData" => $result->message]);
					}
				} else {
					return $result_ctp->setData(["ctpData" => "No Cointopay data found due to empty session values"]);
				}
				 
			}
        }
        return false;
    }

}