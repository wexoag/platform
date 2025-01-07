<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Storefront\Page\Account;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Storefront\Page\Account\CustomerGroupRegistration\CustomerGroupRegistrationPageLoader;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class CustomerGroupRegistrationTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    private IdsCollection $ids;

    private SalesChannelContext $salesChannel;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->salesChannel = $this->createSalesChannelContext();
    }

    public function test404(): void
    {
        static::expectException(CustomerException::class);
        $request = new Request();
        $request->attributes->set('customerGroupId', Defaults::LANGUAGE_SYSTEM);

        $this->getPageLoader()->load($request, $this->salesChannel);
    }

    public function testGetConfiguration(): void
    {
        $customerGroupRepository = static::getContainer()->get('customer_group.repository');
        $customerGroupRepository->create([
            [
                'id' => $this->ids->create('group'),
                'name' => 'foo',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $this->salesChannel->getSalesChannel()->getId()]],
            ],
        ], Context::createDefaultContext());

        $request = new Request();
        $request->attributes->set('customerGroupId', $this->ids->get('group'));

        $page = $this->getPageLoader()->load($request, $this->salesChannel);
        static::assertSame($this->ids->get('group'), $page->getGroup()->getId());
        static::assertSame('test', $page->getGroup()->getRegistrationTitle());
    }

    protected function getPageLoader(): CustomerGroupRegistrationPageLoader
    {
        return static::getContainer()->get(CustomerGroupRegistrationPageLoader::class);
    }
}
