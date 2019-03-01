<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Webhook\Handler\SaleComplete;
use SwagPayPal\Webhook\WebhookEventTypes;

class SaleCompleteTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var SaleComplete
     */
    private $webhookHandler;

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    protected function setUp(): void
    {
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->webhookHandler = $this->createWebhookHandler();
    }

    public function testGetEventType(): void
    {
        static::assertSame(WebhookEventTypes::PAYMENT_SALE_COMPLETED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        $result = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_PAID,
            $context
        )->getId();

        static::assertSame(OrderTransactionRepoMock::ORDER_TRANSACTION_ID, $result['id']);
        static::assertSame($expectedStateId, $result['stateId']);
    }

    private function createWebhookHandler(): SaleComplete
    {
        return new SaleComplete($this->orderTransactionRepo, $this->stateMachineRegistry);
    }
}
