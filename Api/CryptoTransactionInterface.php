<?php 
namespace Crypto\PaymentGateway\Api;
 
 
interface CryptoTransactionInterface {


	/**
	 * GET for Transactions api
	 * @param int $param
	 * @return string
	 */
	
	public function getTransactions($id);
}
