<?php

namespace ContainerOIx4bb4;
include_once \dirname(__DIR__, 4).'/vendor/symfony/http-kernel/DataCollector/DataCollectorInterface.php';
include_once \dirname(__DIR__, 4).'/vendor/symfony/http-kernel/DataCollector/DataCollector.php';
include_once \dirname(__DIR__, 4).'/vendor/symfony/var-dumper/Dumper/DataDumperInterface.php';
include_once \dirname(__DIR__, 4).'/vendor/symfony/http-kernel/DataCollector/DumpDataCollector.php';

class DumpDataCollectorProxy24c9b63 extends \Symfony\Component\HttpKernel\DataCollector\DumpDataCollector implements \Symfony\Component\VarExporter\LazyObjectInterface
{
    use \Symfony\Component\VarExporter\LazyProxyTrait;

    private const LAZY_OBJECT_PROPERTY_SCOPES = [
        "\0".'*'."\0".'data' => [parent::class, 'data', null, 8],
        "\0".'Symfony\\Component\\HttpKernel\\DataCollector\\DataCollector'."\0".'cloner' => ['Symfony\\Component\\HttpKernel\\DataCollector\\DataCollector', 'cloner', null, 16],
        "\0".parent::class."\0".'charset' => [parent::class, 'charset', null, 16],
        "\0".parent::class."\0".'clonesCount' => [parent::class, 'clonesCount', null, 16],
        "\0".parent::class."\0".'clonesIndex' => [parent::class, 'clonesIndex', null, 16],
        "\0".parent::class."\0".'dataCount' => [parent::class, 'dataCount', null, 16],
        "\0".parent::class."\0".'dumper' => [parent::class, 'dumper', null, 16],
        "\0".parent::class."\0".'fileLinkFormat' => [parent::class, 'fileLinkFormat', null, 16],
        "\0".parent::class."\0".'isCollected' => [parent::class, 'isCollected', null, 16],
        "\0".parent::class."\0".'requestStack' => [parent::class, 'requestStack', null, 16],
        "\0".parent::class."\0".'rootRefs' => [parent::class, 'rootRefs', null, 16],
        "\0".parent::class."\0".'sourceContextProvider' => [parent::class, 'sourceContextProvider', null, 16],
        "\0".parent::class."\0".'stopwatch' => [parent::class, 'stopwatch', null, 16],
        "\0".parent::class."\0".'webMode' => [parent::class, 'webMode', null, 16],
        'charset' => [parent::class, 'charset', null, 16],
        'cloner' => ['Symfony\\Component\\HttpKernel\\DataCollector\\DataCollector', 'cloner', null, 16],
        'clonesCount' => [parent::class, 'clonesCount', null, 16],
        'clonesIndex' => [parent::class, 'clonesIndex', null, 16],
        'data' => [parent::class, 'data', null, 8],
        'dataCount' => [parent::class, 'dataCount', null, 16],
        'dumper' => [parent::class, 'dumper', null, 16],
        'fileLinkFormat' => [parent::class, 'fileLinkFormat', null, 16],
        'isCollected' => [parent::class, 'isCollected', null, 16],
        'requestStack' => [parent::class, 'requestStack', null, 16],
        'rootRefs' => [parent::class, 'rootRefs', null, 16],
        'sourceContextProvider' => [parent::class, 'sourceContextProvider', null, 16],
        'stopwatch' => [parent::class, 'stopwatch', null, 16],
        'webMode' => [parent::class, 'webMode', null, 16],
    ];

    public function dump(\Symfony\Component\VarDumper\Cloner\Data $data): ?string
    {
        if (isset($this->lazyObjectState)) {
            return ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->dump(...\func_get_args());
        }

        return parent::dump(...\func_get_args());
    }

    public function collect(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response, ?\Throwable $exception = null): void
    {
        if (isset($this->lazyObjectState)) {
            ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->collect(...\func_get_args());
        } else {
            parent::collect(...\func_get_args());
        }
    }

    public function reset(): void
    {
        if (isset($this->lazyObjectState)) {
            ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->reset(...\func_get_args());
        } else {
            parent::reset(...\func_get_args());
        }
    }

    public function getDumpsCount(): int
    {
        if (isset($this->lazyObjectState)) {
            return ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->getDumpsCount(...\func_get_args());
        }

        return parent::getDumpsCount(...\func_get_args());
    }

    public function getDumps(string $format, int $maxDepthLimit = -1, int $maxItemsPerDepth = -1): array
    {
        if (isset($this->lazyObjectState)) {
            return ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->getDumps(...\func_get_args());
        }

        return parent::getDumps(...\func_get_args());
    }

    public function getName(): string
    {
        if (isset($this->lazyObjectState)) {
            return ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->getName(...\func_get_args());
        }

        return parent::getName(...\func_get_args());
    }

    public function __sleep(): array
    {
        if (isset($this->lazyObjectState)) {
            return ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->__sleep(...\func_get_args());
        }

        return parent::__sleep(...\func_get_args());
    }

    public function __wakeup(): void
    {
        if (isset($this->lazyObjectState)) {
            ($this->lazyObjectState->realInstance ??= ($this->lazyObjectState->initializer)())->__wakeup(...\func_get_args());
        } else {
            parent::__wakeup(...\func_get_args());
        }
    }
}

// Help opcache.preload discover always-needed symbols
class_exists(\Symfony\Component\VarExporter\Internal\Hydrator::class);
class_exists(\Symfony\Component\VarExporter\Internal\LazyObjectRegistry::class);
class_exists(\Symfony\Component\VarExporter\Internal\LazyObjectState::class);

if (!\class_exists('DumpDataCollectorProxy24c9b63', false)) {
    \class_alias(__NAMESPACE__.'\\DumpDataCollectorProxy24c9b63', 'DumpDataCollectorProxy24c9b63', false);
}
