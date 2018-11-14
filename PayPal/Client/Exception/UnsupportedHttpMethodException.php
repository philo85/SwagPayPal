<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Client\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UnsupportedHttpMethodException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-UNSUPPORTED-HTTP-METHOD';

    public function __construct(string $httpMethod, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('An unsupported HTTP method was provided: %s', $httpMethod);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
