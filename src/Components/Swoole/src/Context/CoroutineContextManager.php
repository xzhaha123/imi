<?php

namespace Imi\Swoole\Context;

use ArrayObject;
use Imi\Core\Context\Contract\IContextManager;
use Imi\Core\Context\Exception\ContextExistsException;
use Imi\Core\Context\Exception\ContextNotFoundException;
use Imi\Event\Event;
use Imi\Util\Coroutine;
use Swoole\Coroutine\Context;

/**
 * Swoole 协程上下文管理器.
 */
class CoroutineContextManager implements IContextManager
{
    /**
     * 上下文对象集合.
     *
     * @var Context[]
     */
    private array $contexts;

    /**
     * 创建上下文.
     *
     * @param string $flag
     * @param array  $data
     *
     * @return \ArrayObject
     */
    public function create(string $flag, array $data = []): ArrayObject
    {
        if ($flag > -1)
        {
            $context = Coroutine::getContext($flag);
            // destroy
            if (!($context['__bindDestroy'] ?? false))
            {
                $context['__bindDestroy'] = true;
                Coroutine::defer([$this, '__destroy']);
            }
            foreach ($data as $k => $v)
            {
                $context[$k] = $v;
            }

            return $context;
        }
        else
        {
            if (isset($this->contexts[$flag]))
            {
                throw new ContextExistsException(sprintf('Context %s already exists!', $flag));
            }

            return $this->contexts[$flag] = new ArrayObject($data);
        }
    }

    /**
     * 销毁上下文.
     *
     * @param string $flag
     *
     * @return bool
     */
    public function destroy(string $flag): bool
    {
        if ($flag > -1)
        {
            return false; // 协程退出时自动销毁，无法手动销毁
        }
        elseif (isset($this->contexts[$flag]))
        {
            Event::trigger('IMI.REQUEST_CONTENT.DESTROY');
            unset($this->contexts[$flag]);

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 获取上下文.
     *
     * @param string $flag
     * @param bool   $autoCreate
     *
     * @return \ArrayObject
     */
    public function get(string $flag, bool $autoCreate = false): ArrayObject
    {
        if ($flag > -1)
        {
            $context = Coroutine::getContext($flag);
            // destroy
            if (!($context['__bindDestroy'] ?? false))
            {
                $context['__bindDestroy'] = true;
                Coroutine::defer([$this, '__destroy']);
            }

            return $context;
        }
        else
        {
            if (!isset($this->contexts[$flag]))
            {
                if ($autoCreate)
                {
                    return $this->create($flag);
                }
                throw new ContextNotFoundException(sprintf('Context %s does not exists!', $flag));
            }

            return $this->contexts[$flag];
        }
    }

    /**
     * 上下文是否存在.
     *
     * @param string $flag
     *
     * @return bool
     */
    public function exists(string $flag): bool
    {
        if ($flag > -1)
        {
            return Coroutine::exists($flag);
        }
        else
        {
            return isset($this->contexts[$flag]);
        }
    }

    /**
     * 获取当前上下文标识.
     *
     * @return string
     */
    public function getCurrentFlag(): string
    {
        return Coroutine::getCid();
    }

    /**
     * 销毁当前请求的上下文.
     *
     * 不要手动调用！不要手动调用！不要手动调用！
     *
     * @return void
     */
    public function __destroy()
    {
        Event::trigger('IMI.REQUEST_CONTENT.DESTROY');
        $context = Coroutine::getContext();
        if (!$context)
        {
            $coId = Coroutine::getCid();
            $contextMap = &$this->contextMap;
            if (isset($contextMap[$coId]))
            {
                unset($contextMap[$coId]);
            }
        }
    }
}