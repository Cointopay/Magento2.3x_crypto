<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $paymentInfo = $method->getInfoInstance();
		
		if ($data->getDataByKey('additional_data') !== null) {
			if (array_key_exists('transaction_result', $data->getDataByKey('additional_data'))) {
				$paymentInfo->setAdditionalInformation(
					'transaction_result',
					$data->getDataByKey('additional_data')['transaction_result']
				);

			}
		}
    }
}
