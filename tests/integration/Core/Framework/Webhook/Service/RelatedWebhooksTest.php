<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Framework\Webhook\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Webhook\Service\RelatedWebhooks;
use Shopware\Core\Framework\Webhook\WebhookCollection;
use Shopware\Core\Framework\Webhook\WebhookEntity;

/**
 * @internal
 */
class RelatedWebhooksTest extends TestCase
{
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    /**
     * @var EntityRepository<WebhookCollection>
     */
    private EntityRepository $webhookRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->webhookRepository = $this->getContainer()->get('webhook.repository');

        $this->webhookRepository->upsert([
            [
                'id' => $this->ids->get('wh-1'),
                'name' => 'hook1',
                'eventName' => CustomerBeforeLoginEvent::EVENT_NAME,
                'url' => 'https://test.com',
            ],
            [
                'id' => $this->ids->get('wh-2'),
                'name' => 'hook2',
                'eventName' => CustomerBeforeLoginEvent::EVENT_NAME,
                'url' => 'https://test.com',
            ],
            [
                'id' => $this->ids->get('wh-3'),
                'name' => 'hook3',
                'eventName' => CustomerBeforeLoginEvent::EVENT_NAME,
                'url' => 'https://test.com',
            ],
            [
                'id' => $this->ids->get('wh-4'),
                'name' => 'hook4',
                'eventName' => CustomerBeforeLoginEvent::EVENT_NAME,
                'url' => 'https://test2.com',
            ],
        ], Context::createDefaultContext());
    }

    public function testUpdateRelated(): void
    {
        $relatedWebhooks = $this->getContainer()->get(RelatedWebhooks::class);

        $context = Context::createDefaultContext();
        $relatedWebhooks->updateRelated($this->ids->get('wh-1'), [
            'errorCount' => 2,
        ], $context);

        $webhooks = $this->webhookRepository->search(
            new Criteria(
                [
                    $this->ids->get('wh-1'),
                    $this->ids->get('wh-2'),
                    $this->ids->get('wh-3'),
                ]
            ),
            $context
        );

        $counts = array_values($webhooks->map(fn (WebhookEntity $entity) => $entity->getErrorCount()));
        static::assertSame([2, 2, 2], $counts);
    }
}
