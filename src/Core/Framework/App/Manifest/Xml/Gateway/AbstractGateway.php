<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Manifest\Xml\Gateway;

use Shopware\Core\Framework\App\Manifest\Xml\XmlElement;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
abstract class AbstractGateway extends XmlElement
{
    protected ?string $url = null;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return array{url: string|null}
     */
    protected static function parse(\DOMElement $element): array
    {
        return ['url' => $element->nodeValue];
    }
}
