<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\Event\Listener;

use Imi\Swoole\Server\Event\Param\ManagerStopEventParam;

/**
 * 监听服务器ManagerStop事件接口.
 */
interface IManagerStopEventListener
{
    /**
     * 事件处理方法.
     */
    public function handle(ManagerStopEventParam $e): void;
}
