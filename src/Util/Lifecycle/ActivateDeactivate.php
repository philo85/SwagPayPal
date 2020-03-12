<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Exception\ExistingIZettleSalesChannelsException;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\PaymentMethodUtil;

class ActivateDeactivate
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelTypeRepository;

    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $salesChannelTypeRepository
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->paymentRepository = $paymentRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;
    }

    public function activate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(true, $context);
        $this->addIZettleSalesChannelType($context);
    }

    public function deactivate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(false, $context);
        $this->checkIZettleSalesChannels($context);
        $this->removeIZettleSalesChannelType($context);
    }

    private function setPaymentMethodsIsActive(bool $active, Context $context): void
    {
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);

        if ($payPalPaymentMethodId === null) {
            return;
        }

        $updateData = [[
            'id' => $payPalPaymentMethodId,
            'active' => $active,
        ]];

        $this->paymentRepository->update($updateData, $context);
    }

    private function addIZettleSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE,
                'iconName' => 'default-money-cash',
                'translations' => [
                    'en-GB' => [
                        'name' => 'iZettle integration',
                        'manufacturer' => 'shopware AG',
                        'description' => 'SalesChannel for synchronisation to your iZettle account',
                    ],
                    'de-DE' => [
                        'name' => 'iZettle Integration',
                        'manufacturer' => 'shopware AG',
                        'description' => 'SalesChannel für die Synchronisation zu deinem iZettle Account',
                    ],
                ],
            ],
        ], $context);
    }

    private function removeIZettleSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->delete([['id' => SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE]], $context);
    }

    /**
     * @throws ExistingIZettleSalesChannelsException
     */
    private function checkIZettleSalesChannels(Context $context): void
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(
                new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE)
            );

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($criteria): EntitySearchResult {
            return $this->salesChannelRepository->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            $names = $result->getEntities()->map(function (SalesChannelEntity $item): string {
                return (string) $item->getName();
            });
            throw new ExistingIZettleSalesChannelsException($result->getTotal(), $names);
        }
    }
}
