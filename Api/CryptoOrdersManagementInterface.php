<?php 
namespace Crypto\PaymentGateway\Api;
 
 
interface CryptoOrdersManagementInterface {


	/**
	 * GET for Post api
	 * @param string $param
	 * @return string
	 */
	
	public function getCoin($param);
}
