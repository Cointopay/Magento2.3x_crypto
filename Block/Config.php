<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Crypto\PaymentGateway\Gateway\Response\FraudHandler;

class Config extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @return string | URL
     */
    public function getAjaxUrl()
    {
        return $this->getUrl("cryptocoins");
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
     * Returns value view
     *
     * @return string | Status
     */
    public function cryptoReference ($status) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if (isset($customerSession) && isset($status)) {
            return json_encode('crypto_ref');
        }
        return false;
    }
}
