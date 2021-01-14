<?php

declare(strict_types=1);

namespace Imi\Swoole\Test\TCPServer\MainServer\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Swoole\Server\TcpServer\IReceiveHandler;
use Imi\Swoole\Server\TcpServer\Message\IReceiveData;
use Imi\Swoole\Server\TcpServer\Middleware\IMiddleware;

/**
 * @Bean
 */
class Test implements IMiddleware
{
    public function process(IReceiveData $data, IReceiveHandler $handler)
    {
        RequestContext::set('middlewareData', 'imi');

        return $handler->handle($data, $handler);
    }
}