<?php

declare(strict_types=1);

namespace Imi\Model\Event\Listener;

use Imi\Model\Event\Param\BeforeSelectEventParam;

/**
 * 模型 查询前 事件监听接口.
 */
interface IBeforeSelectEventListener
{
    /**
     * 事件处理方法.
     */
    public function handle(BeforeSelectEventParam $e): void;
}
