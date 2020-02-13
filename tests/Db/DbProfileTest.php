<?php

declare(strict_types=1);

namespace BEAR\Dev\Db;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

class DbProfileTest extends TestCase
{
    /**
     * @var DbProfile
     */
    private $dbProfile;

    protected function setUp() : void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure() : void
            {
                $this->bind(ExtendedPdoInterface::class)->toInstance(new ExtendedPdo('sqlite::memory:'));
                $this->bind(LoggerInterface::class)->toInstance(new NullLogger);
            }
        });
        $this->dbProfile = new DbProfile($injector);
    }

    public function testTearDown() : void
    {
        $profiles = $this->dbProfile->log();
        $this->assertIsArray($profiles);
    }
}
