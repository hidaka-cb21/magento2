<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Test\Unit\Model\Order;

use \Magento\Sales\Model\Order;

/**
 * Test for \Magento\Sales\Model\Order\OrderValidator class
 */
class OrderValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\OrderValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $model;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderItemMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->orderMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus', 'getItems'])
            ->getMockForAbstractClass();

        $this->orderItemMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQtyToInvoice', 'getLockedDoInvoice'])
            ->getMockForAbstractClass();

        $this->model = new \Magento\Sales\Model\Order\OrderValidator();
    }

    /**
     * @param string $state
     *
     * @dataProvider canInvoiceWrongStateDataProvider
     */
    public function testCanInvoiceWrongState($state)
    {
        $this->orderMock->expects($this->any())
            ->method('getState')
            ->willReturn($state);
        $this->orderMock->expects($this->never())
            ->method('getItems');
        $this->assertEquals(
            false,
            $this->model->canInvoice($this->orderMock)
        );
    }

    /**
     * Data provider for testCanInvoiceWrongState
     * @return array
     */
    public function canInvoiceWrongStateDataProvider()
    {
        return [
            [Order::STATE_PAYMENT_REVIEW],
            [Order::STATE_HOLDED],
            [Order::STATE_CANCELED],
            [Order::STATE_COMPLETE],
            [Order::STATE_CLOSED],
        ];
    }

    public function testCanInvoiceNoItems()
    {
        $this->orderMock->expects($this->any())
            ->method('getState')
            ->willReturn(Order::STATE_PROCESSING);

        $this->orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->assertEquals(
            false,
            $this->model->canInvoice($this->orderMock)
        );
    }

    /**
     * @param float $qtyToInvoice
     * @param bool|null $itemLockedDoInvoice
     * @param bool $expectedResult
     *
     * @dataProvider canInvoiceDataProvider
     */
    public function testCanInvoice($qtyToInvoice, $itemLockedDoInvoice, $expectedResult)
    {
        $this->orderMock->expects($this->any())
            ->method('getState')
            ->willReturn(Order::STATE_PROCESSING);

        $items = [$this->orderItemMock];
        $this->orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn($items);
        $this->orderItemMock->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn($qtyToInvoice);
        $this->orderItemMock->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn($itemLockedDoInvoice);

        $this->assertEquals(
            $expectedResult,
            $this->model->canInvoice($this->orderMock)
        );
    }

    /**
     * Data provider for testCanInvoice
     *
     * @return array
     */
    public function canInvoiceDataProvider()
    {
        return [
            [0, null, false],
            [-1, null, false],
            [1, true, false],
            [0.5, false, true],
        ];
    }
}
