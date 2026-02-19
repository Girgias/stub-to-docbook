<?php

namespace Girgias\StubToDocbook;

final class ExclusionList
{
    /**
     * @param list<string> $constantNames Exact constant names to exclude
     * @param list<string> $functionNames Exact function names to exclude
     * @param list<string> $classNames Exact class names to exclude
     * @param list<string> $stubFiles Stub file paths to exclude
     * @param list<string> $docFiles Documentation file paths to exclude
     * @param list<string> $namePatterns Regex patterns to match symbol names to exclude
     */
    public function __construct(
        readonly array $constantNames = [],
        readonly array $functionNames = [],
        readonly array $classNames = [],
        readonly array $stubFiles = [],
        readonly array $docFiles = [],
        readonly array $namePatterns = [],
    ) {}

    public function isConstantExcluded(string $name): bool
    {
        return in_array($name, $this->constantNames, true)
            || $this->matchesPattern($name);
    }

    public function isFunctionExcluded(string $name): bool
    {
        return in_array($name, $this->functionNames, true)
            || $this->matchesPattern($name);
    }

    public function isClassExcluded(string $name): bool
    {
        return in_array($name, $this->classNames, true)
            || $this->matchesPattern($name);
    }

    public function isStubFileExcluded(string $path): bool
    {
        foreach ($this->stubFiles as $excluded) {
            if (str_ends_with($path, $excluded)) {
                return true;
            }
        }
        return false;
    }

    public function isDocFileExcluded(string $path): bool
    {
        foreach ($this->docFiles as $excluded) {
            if (str_ends_with($path, $excluded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @template T
     * @param array<string, T> $symbols
     * @param callable(string): bool $excludeCheck
     * @return array<string, T>
     */
    public static function filterMap(array $symbols, callable $excludeCheck): array
    {
        return array_filter(
            $symbols,
            fn (string $name) => !$excludeCheck($name),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function matchesPattern(string $name): bool
    {
        foreach ($this->namePatterns as $pattern) {
            if (preg_match($pattern, $name) === 1) {
                return true;
            }
        }
        return false;
    }
}
