<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;

/**
 * @internal
 */
#[Package('checkout')]
class SEPAHandlerTest extends AbstractTestSyncAPMHandler
{
    protected function getPaymentHandlerClassName(): string
    {
        return SEPAHandler::class;
    }
}
