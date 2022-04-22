<?php

namespace Crypto\PaymentGateway\Plugin\Order\Handler;

use Magento\Sales\Model\Order;

/**
 * Class State
 */
class State
{
    /**
     * Check order status and adjust the status before save for check money orders
     * @param \Magento\Sales\Model\ResourceModel\Order\Handler\State $subject
     * @param $result
     * @param Order $order
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheck(\Magento\Sales\Model\ResourceModel\Order\Handler\State $subject, $result,  Order $order)
    {

        if($order->getEntityType() == 'order' && $order->getPayment()->getMethod() == 'crypto_gateway'){
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus('processing');
        }

        return $result;
    }
}