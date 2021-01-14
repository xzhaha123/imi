<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\ConnectContext\Listener;

use Imi\App;
use Imi\Bean\Annotation\Listener;
use Imi\RequestContext;
use Imi\ServerManage;
use Imi\Swoole\Server\Event\Listener\IAppInitEventListener;
use Imi\Swoole\Server\Event\Param\AppInitEventParam;
use Imi\Util\Imi;

/**
 * @Listener(eventName="IMI.APP.INIT")
 */
class AppInit implements IAppInitEventListener
{
    /**
     * 事件处理方法.
     *
     * @param EventParam $e
     *
     * @return void
     */
    public function handle(AppInitEventParam $e)
    {
        foreach (ServerManage::getServers() as $server)
        {
            if ($server->isLongConnection())
            {
                RequestContext::set('server', $server);
                $server->getBean('ConnectContextStore')->getHandler();
                if (Imi::getClassPropertyValue('ServerGroup', 'status'))
                {
                    /** @var \Imi\Swoole\Server\Group\Handler\IGroupHandler $groupHandler */
                    $groupHandler = $server->getBean(Imi::getClassPropertyValue('ServerGroup', 'groupHandler'));
                    $groupHandler->clear();
                }
                App::getBean('ConnectionBinder');
            }
        }
    }
}