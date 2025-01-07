<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Checkout\Promotion\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Promotion\DataAbstractionLayer\PromotionIndexer;
use Shopware\Core\Checkout\Promotion\DataAbstractionLayer\PromotionIndexingMessage;
use Shopware\Core\Checkout\Promotion\PromotionDefinition;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Integration\Traits\CustomerTestTrait;
use Shopware\Core\Test\Integration\Traits\Promotion\PromotionTestFixtureBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class PromotionIndexerTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;
    use PromotionTestFixtureBehaviour;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testPromotionIndexerUpdateReturnNullIfGeneratingCode(): void
    {
        $indexer = static::getContainer()->get(PromotionIndexer::class);

        $salesChannelContext = $this->createSalesChannelContext();

        /** @var EntityRepository $promotionRepository */
        $promotionRepository = static::getContainer()->get('promotion.repository');

        /** @var EntityRepository $promotionIndividualRepository */
        $promotionIndividualRepository = static::getContainer()->get('promotion_individual_code.repository');

        $voucherA = $this->ids->create('voucherA');

        $writtenEvent = $this->createPromotion($voucherA, $voucherA, $promotionRepository, $salesChannelContext);
        $promotionEvent = $writtenEvent->getEventByEntityName(PromotionDefinition::ENTITY_NAME);

        static::assertNotNull($promotionEvent);
        static::assertNotEmpty($promotionEvent->getWriteResults()[0]);
        $promotionId = $promotionEvent->getWriteResults()[0]->getPayload()['id'];

        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $origin->setIsAdmin(true);
        $context = Context::createDefaultContext($origin);

        $event = $this->createIndividualCode($promotionId, 'CODE-1', $promotionIndividualRepository, $context);

        $result = $indexer->update($event);

        static::assertNull($result);
    }

    public function testPromotionIndexerUpdateReturnPromotionIndexingMessage(): void
    {
        $indexer = static::getContainer()->get(PromotionIndexer::class);

        $salesChannelContext = $this->createSalesChannelContext();

        /** @var EntityRepository $promotionRepository */
        $promotionRepository = static::getContainer()->get('promotion.repository');

        $voucherA = $this->ids->create('voucherA');

        $writtenEvent = $this->createPromotion($voucherA, $voucherA, $promotionRepository, $salesChannelContext);

        $result = $indexer->update($writtenEvent);

        static::assertInstanceOf(PromotionIndexingMessage::class, $result);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createSalesChannelContext(array $options = []): SalesChannelContext
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);

        $token = Uuid::randomHex();

        return $salesChannelContextFactory->create($token, TestDefaults::SALES_CHANNEL, $options);
    }
}
