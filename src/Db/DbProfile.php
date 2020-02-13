<?php

declare(strict_types=1);

namespace BEAR\Dev\Db;

use Aura\Sql\ExtendedPdoInterface;
use Aura\Sql\Profiler;
use Psr\Log\LoggerInterface;
use Ray\Di\InjectorInterface;

final class DbProfile
{
    /**
     * @var ExtendedPdoInterface
     */
    private $pdo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(InjectorInterface $injector)
    {
        $this->pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $this->pdo->setProfiler(new Profiler);
        $this->pdo->getProfiler()->setActive(true);
        $this->logger = $injector->getInstance(LoggerInterface::class);
    }

    /**
     * @return array<string>
     */
    public function log() : array
    {
        $profiles = $this->pdo->getProfiler()->getProfiles();
        foreach ($profiles as &$profile) {
            unset($profile['trace'], $profile['duration']);
        }
        unset($profile);
        if ($profiles) {
            $this->logger->debug('sql:', $profiles);
        }

        return $profiles;
    }
}
