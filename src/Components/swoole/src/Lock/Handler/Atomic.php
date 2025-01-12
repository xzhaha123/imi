<?php

declare(strict_types=1);

namespace Imi\Swoole\Lock\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Lock\Handler\BaseLock;
use Imi\Swoole\Util\AtomicManager;
use Swoole\Timer;

/**
 * Atomic 实现的多进程单机锁，注意会阻塞进程，只推荐在自定义进程、进程池中使用.
 *
 * @Bean("AtomicLock")
 */
class Atomic extends BaseLock
{
    /**
     * 配置的 Atomic 名称.
     */
    public string $atomicName = '';

    /**
     * 同时获得锁的数量.
     */
    public int $quantity = 1;

    /**
     * 超时 timerid.
     */
    private ?int $timeoutTimerId = null;

    /**
     * 加锁，会阻塞进程.
     */
    protected function __lock(): bool
    {
        $result = AtomicManager::wait($this->atomicName, 0 === $this->waitTimeout ? -1 : ($this->waitTimeout / 1000));
        if ($result)
        {
            $this->startTimeoutTimer();
        }

        return $result;
    }

    /**
     * 尝试获取锁
     */
    protected function __tryLock(): bool
    {
        $result = AtomicManager::wait($this->atomicName, 0.001);
        if ($result)
        {
            $this->startTimeoutTimer();
        }

        return $result;
    }

    /**
     * 解锁
     */
    protected function __unlock(): bool
    {
        $result = AtomicManager::wakeup($this->atomicName, $this->quantity);
        $this->stopTimeoutTimer();

        return $result;
    }

    /**
     * 开启超时计时器.
     */
    private function startTimeoutTimer(): void
    {
        if ($this->timeoutTimerId)
        {
            $this->stopTimeoutTimer();
        }
        $this->timeoutTimerId = Timer::after($this->lockExpire, function () {
            $this->unlock();
        });
    }

    /**
     * 停止超时计时器.
     */
    private function stopTimeoutTimer(): void
    {
        Timer::clear($this->timeoutTimerId);
        $this->timeoutTimerId = null;
    }
}
