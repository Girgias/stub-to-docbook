<?php

namespace Girgias\StubToDocbook\Stubs;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

class ZendEngineSingleFileSourceLocator extends SingleFileSourceLocator
{
// InternalLocatedSource

    /**
     * @param non-empty-string $fileName
     *
     * @throws InvalidFileLocation
     */
    public function __construct(private readonly string $fileName, Locator $astLocator)
    {
        parent::__construct($fileName, $astLocator);
    }

    protected function createLocatedSource(Identifier $identifier): LocatedSource|null
    {
        $content = file_get_contents($this->fileName);
        if ($content === false || strlen($content) === 0) {
            throw new \Exception('Cannot load content of "' . $this->fileName . '"');
        }
        return new InternalLocatedSource(
            $content,
            $identifier->getName(),
            self::getExtensionNameFromFileName($this->fileName),
        );
    }

    /**
     * @param non-empty-string $fileName
     * @return non-empty-string
     */
    private static function getExtensionNameFromFileName(string $fileName): string
    {
        if (str_contains($fileName, 'Zend/')) {
            return 'Core';
        } else if (str_contains($fileName, 'main/')) {
            return 'PHP (main/)';
        } else if (str_contains($fileName, 'ext/')) {
            $start = strpos($fileName, 'ext/') + strlen('ext/');
            $end = strpos($fileName, '/', $start);
            /** @var non-empty-string */
            return ucfirst(substr($fileName, $start, $end - $start));
        } else if (str_contains($fileName, 'sapi/')) {
            $start = strpos($fileName, 'sapi/') + strlen('sapi/');
            $end = strpos($fileName, '/', $start);
            return 'SAPI ' . ucfirst(substr($fileName, $start, $end - $start));
        } else {
            throw new \Exception("Cannot deal with file name '{$fileName}'");
        }
    }
}
