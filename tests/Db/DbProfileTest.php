<?php

declare(strict_types=1);

namespace BEAR\Dev\Db;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\InjectorInterface;

class DbProfileTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    public static $log;

    /** @var ExtendedPdoInterface */
    private $pdo;

    /** @var InjectorInterface */
    private $injector;

    protected function setUp(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(ExtendedPdoInterface::class)->toInstance(new ExtendedPdo('sqlite::memory:'));
                $this->bind(LoggerInterface::class)->toInstance(
                    new class extends AbstractLogger {
                        public function log($level, $message, array $context = []): void
                        {
                            DbProfileTest::$log[] = $context;
                        }
                    }
                );
            }
        });
        $this->pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $this->injector = $injector;
    }

    public function testLog(): void
    {
        new DbProfile($this->injector); // Start SQL log
        $this->pdo->exec(/** @lang sql */'CREATE TABLE user(name, age)');
        $this->pdo->perform(/** @lang sql */'INSERT INTO user (name, age) VALUES (?, ?)', ['koriym', 18]);
        $this->assertCount(3, self::$log);
        $this->assertSame('connect', self::$log[0]['function']);
        $this->assertSame('exec', self::$log[1]['function']);
        $this->assertSame('perform', self::$log[2]['function']);
    }
}
