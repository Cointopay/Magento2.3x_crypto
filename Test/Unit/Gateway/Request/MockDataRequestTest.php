<?php
/**
 * Copyright © 2018 Crypto. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Crypto\PaymentGateway\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Crypto\PaymentGateway\Gateway\Http\Client\ClientMock;
use Crypto\PaymentGateway\Gateway\Request\MockDataRequest;

class MockDataRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param int $forceResultCode
     * @param int|null $transactionResult
     *
     * @dataProvider transactionResultsDataProvider
     */
    public function testBuild($forceResultCode, $transactionResult)
    {
        $expectation = [
            MockDataRequest::FORCE_RESULT => $forceResultCode
        ];

        $paymentDO = $this->getMock(PaymentDataObjectInterface::class);
        $paymentModel = $this->getMock(InfoInterface::class);


        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentModel);

        $paymentModel->expects(static::once())
            ->method('getAdditionalInformation')
            ->with('transaction_result')
            ->willReturn(
                $transactionResult
            );

        $request = new MockDataRequest();

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentDO])
        );
    }

    /**
     * @return array
     */
    public function transactionResultsDataProvider()
    {
        return [
            [
                'forceResultCode' => ClientMock::SUCCESS,
                'transactionResult' => null
            ],
            [
                'forceResultCode' => ClientMock::SUCCESS,
                'transactionResult' => ClientMock::SUCCESS
            ],
            [
                'forceResultCode' => ClientMock::FAILURE,
                'transactionResult' => ClientMock::FAILURE
            ]
        ];
    }
}
