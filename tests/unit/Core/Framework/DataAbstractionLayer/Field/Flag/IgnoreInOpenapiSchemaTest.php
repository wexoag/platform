<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Field\Flag;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\IgnoreInOpenapiSchema;

/**
 * @internal
 */
#[CoversClass(IgnoreInOpenapiSchema::class)]
class IgnoreInOpenapiSchemaTest extends TestCase
{
    public function testIisBaseUrlAllowedReturnsTrueForAllowedBaseUrl(): void
    {
        $flag = new IgnoreInOpenapiSchema(AdminApiSource::class);
        static::assertTrue($flag->isBaseUrlAllowed('/api/'));
    }

    public function testIsBaseUrlAllowedReturnsFalseForDisallowedBaseUrl(): void
    {
        $flag = new IgnoreInOpenapiSchema(AdminApiSource::class);
        static::assertFalse($flag->isBaseUrlAllowed('/unknown-api/'));
    }

    public function testIsSourceAllowedReturnsTrueForAllowedSource(): void
    {
        $flag = new IgnoreInOpenapiSchema(AdminApiSource::class);
        static::assertTrue($flag->isSourceAllowed(AdminApiSource::class));
    }

    public function testIsSourceAllowedReturnsFalseForDisallowedSource(): void
    {
        $flag = new IgnoreInOpenapiSchema(AdminApiSource::class);
        static::assertFalse($flag->isSourceAllowed(SalesChannelApiSource::class));
    }

    public function testIsSourceAllowedReturnsTrueForSystemSource(): void
    {
        $flag = new IgnoreInOpenapiSchema();
        static::assertTrue($flag->isSourceAllowed(SystemSource::class));
    }

    public function testParseReturnsWhitelistKeys(): void
    {
        $flag = new IgnoreInOpenapiSchema(AdminApiSource::class, SalesChannelApiSource::class);
        $result = iterator_to_array($flag->parse());
        static::assertEquals(
            ['read_protected' => [[AdminApiSource::class, SalesChannelApiSource::class]]],
            $result
        );
    }
}
