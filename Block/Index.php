<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    public function getOrderOutput(){
        return $this->getMessage();
    }

}