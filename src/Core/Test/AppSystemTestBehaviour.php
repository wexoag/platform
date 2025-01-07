<?php declare(strict_types=1);

namespace Shopware\Core\Test;

use Psr\Log\NullLogger;
use Shopware\Core\Framework\App\AppService;
use Shopware\Core\Framework\App\Lifecycle\AppLifecycle;
use Shopware\Core\Framework\App\Lifecycle\AppLifecycleIterator;
use Shopware\Core\Framework\App\Lifecycle\AppLoader;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Script\Debugging\ScriptTraces;
use Shopware\Core\System\Snippet\Files\SnippetFileCollection;
use Shopware\Core\System\Snippet\Files\SnippetFileLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait AppSystemTestBehaviour
{
    abstract protected static function getContainer(): ContainerInterface;

    protected function getAppLoader(string $appDir): AppLoader
    {
        return new AppLoader(
            $appDir,
            new NullLogger()
        );
    }

    protected function loadAppsFromDir(string $appDir, bool $activateApps = true): void
    {
        $appService = new AppService(
            new AppLifecycleIterator(
                static::getContainer()->get('app.repository'),
                $this->getAppLoader($appDir),
            ),
            static::getContainer()->get(AppLifecycle::class)
        );

        $fails = $appService->doRefreshApps($activateApps, Context::createDefaultContext());

        if ($fails !== []) {
            $errors = \array_map(function (array $fail): string {
                return $fail['exception']->getMessage();
            }, $fails);

            static::fail('App synchronisation failed: ' . \print_r($errors, true));
        }
    }

    protected function reloadAppSnippets(): void
    {
        $collection = static::getContainer()->get(SnippetFileCollection::class);
        $collection->clear();
        static::getContainer()->get(SnippetFileLoader::class)->loadSnippetFilesIntoCollection($collection);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptTraces(): array
    {
        return static::getContainer()
            ->get(ScriptTraces::class)
            ->getTraces();
    }
}
