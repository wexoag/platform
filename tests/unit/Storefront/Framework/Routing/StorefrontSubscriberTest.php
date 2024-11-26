<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Storefront\Framework\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Shopware\Core\Content\Seo\HreflangLoaderInterface;
use Shopware\Core\Framework\App\ActiveAppsLoader;
use Shopware\Core\Framework\App\ShopId\ShopIdProvider;
use Shopware\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent;
use Shopware\Core\Framework\Routing\Exception\CustomerNotLoggedInRoutingException;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Framework\Routing\MaintenanceModeResolver;
use Shopware\Storefront\Framework\Routing\StorefrontSubscriber;
use Shopware\Storefront\Theme\StorefrontPluginRegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(StorefrontSubscriber::class)]
class StorefrontSubscriberTest extends TestCase
{
    public function testHasEvents(): void
    {
        $expected = [
            KernelEvents::REQUEST => [
                ['startSession', 40],
                ['maintenanceResolver'],
            ],
            KernelEvents::EXCEPTION => [
                ['customerNotLoggedInHandler'],
                ['maintenanceResolver'],
            ],
            KernelEvents::CONTROLLER => [
                ['preventPageLoadingFromXmlHttpRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE],
            ],
            CustomerLoginEvent::class => [
                'updateSessionAfterLogin',
            ],
            CustomerLogoutEvent::class => [
                'updateSessionAfterLogout',
            ],
            StorefrontRenderEvent::class => [
                ['addHreflang'],
                ['addShopIdParameter'],
                ['addIconSetConfig'],
            ],
            SalesChannelContextResolvedEvent::class => [
                ['replaceContextToken'],
            ],
        ];

        static::assertSame($expected, StorefrontSubscriber::getSubscribedEvents());
    }

    public function testRedirectLoginPageWhenCustomerNotLoggedInWithRoutingException(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(static::once())
            ->method('generate')
            ->with('frontend.account.login.page')
            ->willReturn('/login');

        $subscriber = new StorefrontSubscriber(
            $this->createMock(RequestStack::class),
            $router,
            $this->createMock(HreflangLoaderInterface::class),
            $this->createMock(MaintenanceModeResolver::class),
            $this->createMock(ShopIdProvider::class),
            $this->createMock(ActiveAppsLoader::class),
            new StaticSystemConfigService(),
            $this->createMock(StorefrontPluginRegistryInterface::class)
        );

        $exception = new CustomerNotLoggedInRoutingException(Response::HTTP_FORBIDDEN, RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, 'Customer is not logged in.');
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, true);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $subscriber->customerNotLoggedInHandler($event);

        static::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testRedirectLoginPageWhenCustomerNotLoggedInWithCustomerNotLoggedInException(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(static::once())
            ->method('generate')
            ->with('frontend.account.login.page')
            ->willReturn('/login');

        $subscriber = new StorefrontSubscriber(
            $this->createMock(RequestStack::class),
            $router,
            $this->createMock(HreflangLoaderInterface::class),
            $this->createMock(MaintenanceModeResolver::class),
            $this->createMock(ShopIdProvider::class),
            $this->createMock(ActiveAppsLoader::class),
            new StaticSystemConfigService(),
            $this->createMock(StorefrontPluginRegistryInterface::class)
        );

        $exception = new CustomerNotLoggedInException(Response::HTTP_FORBIDDEN, RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, 'Foo test');
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, true);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $subscriber->customerNotLoggedInHandler($event);

        static::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testCustomerNotLoggedInHandlerWithoutRedirect(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(static::never())
            ->method('generate')
            ->with('frontend.account.login.page')
            ->willReturn('/login');

        $subscriber = new StorefrontSubscriber(
            $this->createMock(RequestStack::class),
            $router,
            $this->createMock(HreflangLoaderInterface::class),
            $this->createMock(MaintenanceModeResolver::class),
            $this->createMock(ShopIdProvider::class),
            $this->createMock(ActiveAppsLoader::class),
            new StaticSystemConfigService(),
            $this->createMock(StorefrontPluginRegistryInterface::class)
        );

        $exception = new RoutingException(Response::HTTP_FORBIDDEN, 'foo', 'You have to be logged in to access this page');
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, true);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $subscriber->customerNotLoggedInHandler($event);
    }
}
