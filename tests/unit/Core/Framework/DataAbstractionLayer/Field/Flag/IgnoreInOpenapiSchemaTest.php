<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Field\Flag;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\IgnoreInOpenapiSchema;

/**
 * @internal
 */
#[CoversClass(IgnoreInOpenapiSchema::class)]
class IgnoreInOpenapiSchemaTest extends TestCase
{
    public function testParseReturnsWhitelistKeys(): void
    {
        $flag = new IgnoreInOpenapiSchema();
        $generator = $flag->parse();
        $result = iterator_to_array($generator);

        static::assertSame(['ignore_in_openapi_schema' => true], $result);
    }
}
