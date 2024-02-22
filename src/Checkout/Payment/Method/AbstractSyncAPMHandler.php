<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

#[Package('checkout')]
abstract class AbstractSyncAPMHandler extends AbstractPaymentMethodHandler implements SynchronousPaymentHandlerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly OrderExecuteService $orderExecuteService,
        private readonly OrderPatchService $orderPatchService,
        private readonly TransactionDataService $transactionDataService,
        private readonly LoggerInterface $logger,
        private readonly OrderResource $orderResource
    ) {
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);

        if (!$paypalOrderId) {
            throw new SyncPaymentProcessException($transactionId, 'Missing PayPal order id');
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());

            $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());

            $this->transactionDataService->setOrderId(
                $transactionId,
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );

            $this->orderPatchService->patchOrder(
                $transaction->getOrder(),
                $transaction->getOrderTransaction(),
                $salesChannelContext,
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP
            );

            $paypalOrder = $this->executeOrder(
                $transaction,
                $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId()),
                $salesChannelContext
            );

            $this->transactionDataService->setResourceId($paypalOrder, $transactionId, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw new SyncPaymentProcessException($transactionId, $e->getMessage());
        }
    }

    protected function executeOrder(SyncPaymentTransactionStruct $transaction, Order $paypalOrder, SalesChannelContext $salesChannelContext): Order
    {
        return $this->orderExecuteService->captureOrAuthorizeOrder(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrder,
            $salesChannelContext->getSalesChannelId(),
            $salesChannelContext->getContext(),
            PartnerAttributionId::PAYPAL_PPCP,
        );
    }
}
