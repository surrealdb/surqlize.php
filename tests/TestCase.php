<?php

declare(strict_types=1);

namespace Surqlize\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Skip the current test when required surqlize API classes are not implemented yet.
     *
     * @param class-string|string ...$classes
     */
    protected static function requireApi(string ...$classes): void
    {
        foreach ($classes as $class) {
            if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
                self::markTestSkipped(sprintf('Pending API: %s not implemented yet.', $class));
            }
        }
    }

    protected static function requireMethod(string $class, string $method): void
    {
        self::requireApi($class);

        if (! method_exists($class, $method)) {
            self::markTestSkipped(sprintf('Pending API: %s::%s() not implemented yet.', $class, $method));
        }
    }
}
