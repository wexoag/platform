<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\LogoutRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Integration\Traits\CustomerTestTrait;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Core\Test\TestDefaults;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class LogoutRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
    }

    public function testNotLoggedin(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/logout',
            );

        static::assertIsString($this->browser->getResponse()->getContent());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        if (Feature::isActive('v6.7.0.0')) {
            static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
        } else {
            static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
        }
    }

    public function testValidLogout(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'shopware',
                ]
            );

        static::assertIsString($this->browser->getResponse()->getContent());

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/logout',
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/customer'
            );

        static::assertIsString($this->browser->getResponse()->getContent());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
    }

    public function testLogoutKeepsCartToBeAbleToRestore(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'shopware',
                ]
            );

        static::assertIsString($this->browser->getResponse()->getContent());

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/logout',
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $tokens = static::getContainer()->get(Connection::class)
            ->fetchFirstColumn('SELECT token FROM sales_channel_api_context WHERE customer_id =  (SELECT id FROM customer where email = ?)', [$email]);

        static::assertCount(1, $tokens);
        static::assertNotContains($contextToken, $tokens, 'Old token should still exist');
    }

    public function testLoggedOutKeepCustomerContextWithoutReplaceTokenParameter(): void
    {
        $systemConfig = static::getContainer()->get(SystemConfigService::class);
        $systemConfig->set('core.loginRegistration.invalidateSessionOnLogOut', false);

        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'shopware',
                ]
            );

        static::assertIsString($this->browser->getResponse()->getContent());

        $response = $this->browser->getResponse();

        $currentCustomerToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?: '';

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $currentCustomerToken);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/logout',
            );

        $customerIdWithOldToken = static::getContainer()->get(Connection::class)->fetchOne('SELECT customer_id FROM sales_channel_api_context WHERE token = ?', [$currentCustomerToken]);
        static::assertFalse($customerIdWithOldToken, 'The old token should be gone');
    }

    public function testLogoutRouteReturnContextTokenResponse(): void
    {
        $systemConfig = static::getContainer()->get(SystemConfigService::class);
        $systemConfig->set('core.loginRegistration.invalidateSessionOnLogOut', false);

        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $contextToken = Random::getAlphanumericString(32);

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            $contextToken,
            TestDefaults::SALES_CHANNEL,
            []
        );

        $request = new RequestDataBag(['email' => $email, 'password' => 'shopware']);
        $loginResponse = static::getContainer()->get(LoginRoute::class)->login($request, $salesChannelContext);

        $customerId = $this->createCustomer();
        $customer = static::getContainer()
            ->get('customer.repository')
            ->search(new Criteria(), Context::createDefaultContext())
            ->get($customerId);
        static::assertInstanceOf(CustomerEntity::class, $customer);
        $customer->setGuest(false);
        $salesChannelContext->assign([
            'token' => $loginResponse->getToken(),
            'customer' => $customer,
        ]);

        $logoutResponse = static::getContainer()->get(LogoutRoute::class)->logout(
            $salesChannelContext,
            new RequestDataBag()
        );

        static::assertInstanceOf(ContextTokenResponse::class, $logoutResponse);
        static::assertNotEquals($loginResponse->getToken(), $logoutResponse->getToken());
    }

    public function testLogoutForcedForGuestAccounts(): void
    {
        $config = static::getContainer()->get(SystemConfigService::class);
        $config->set('core.loginRegistration.invalidateSessionOnLogOut', false);

        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, []);

        $request = new RequestDataBag(['email' => $email, 'password' => 'shopware']);
        $login = static::getContainer()
            ->get(LoginRoute::class)
            ->login($request, $context);

        $customerId = $this->createCustomer();
        $customer = static::getContainer()
            ->get('customer.repository')
            ->search(new Criteria(), Context::createDefaultContext())
            ->get($customerId);
        static::assertInstanceOf(CustomerEntity::class, $customer);
        $customer->setGuest(true);
        $context->assign([
            'token' => $login->getToken(),
            'customer' => $customer,
        ]);

        $logout = static::getContainer()
            ->get(LogoutRoute::class)
            ->logout($context, $request);

        static::assertInstanceOf(ContextTokenResponse::class, $logout);
        static::assertNotEquals($login->getToken(), $logout->getToken());

        $exists = static::getContainer()->get(Connection::class)
            ->fetchAllAssociative('SELECT * FROM sales_channel_api_context WHERE token = :token', ['token' => $login->getToken()]);

        static::assertEmpty($exists);
    }

    public function testValidLogoutAsGuest(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email, true);
        $this->browser->setServerParameter(
            'HTTP_SW_CONTEXT_TOKEN',
            $this->getLoggedInContextToken($customerId, $this->ids->get('sales-channel'))
        );

        $this->browser
            ->request(
                'POST',
                '/store-api/account/logout',
            );

        static::assertIsString($this->browser->getResponse()->getContent());
        static::assertSame(
            200,
            $this->browser->getResponse()->getStatusCode(),
            $this->browser->getResponse()->getContent()
        );

        $this->browser
            ->request(
                'POST',
                '/store-api/account/customer'
            );

        static::assertIsString($this->browser->getResponse()->getContent());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
    }
}
