<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\App\Manifest\Xml\Gateways;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\App\Manifest\Manifest;
use Shopware\Core\Framework\App\Manifest\Xml\Gateway\Gateways;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[CoversClass(Gateways::class)]
#[Package('core')]
class GatewaysTest extends TestCase
{
    public function testParse(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/testGateway/manifest.xml');

        static::assertNotNull($manifest->getGateways());

        $gateways = $manifest->getGateways();

        static::assertNotNull($gateways->getCheckout());
        static::assertNotNull($gateways->getInAppPurchasesGateway());
    }
}
