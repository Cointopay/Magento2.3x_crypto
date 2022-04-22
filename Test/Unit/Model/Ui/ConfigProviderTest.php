<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Test\Unit\Model\Ui;

use Crypto\PaymentGateway\Gateway\Http\Client\ClientMock;
use Crypto\PaymentGateway\Model\Ui\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $configProvider = new ConfigProvider();

        static::assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'transactionResults' => [
                            ClientMock::SUCCESS => __('Success'),
                            ClientMock::FAILURE => __('Fraud')
                        ]
                    ]
                ]
            ],
            $configProvider->getConfig()
        );
    }
}
