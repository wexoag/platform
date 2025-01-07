<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Content\Product\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Exception\DuplicateProductSearchConfigFieldException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
class ProductSearchConfigFieldExceptionHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testDuplicateInsert(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement('DELETE FROM product_search_config');

        $ids = new IdsCollection();
        $config = [
            'id' => $ids->get('config'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'andLogic' => true,
            'minSearchLength' => 3,
            'configFields' => [
                ['id' => $ids->get('field-1'), 'field' => 'test'],
                ['id' => $ids->get('field-2'), 'field' => 'test'],
            ],
        ];

        static::expectException(DuplicateProductSearchConfigFieldException::class);
        static::expectExceptionMessage('Product search config with field test already exists.');

        static::getContainer()->get('product_search_config.repository')
            ->create([$config], Context::createDefaultContext());
    }
}
