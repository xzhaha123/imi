<?php

declare(strict_types=1);

namespace Imi\Model\Event\Listener;

use Imi\Model\Event\Param\BeforeInsertEventParam;

/**
 * 模型 插入前 事件监听接口.
 */
interface IBeforeInsertEventListener
{
    /**
     * 事件处理方法.
     */
    public function handle(BeforeInsertEventParam $e): void;
}
