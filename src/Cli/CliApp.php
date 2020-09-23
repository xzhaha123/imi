<?php

namespace Imi\Cli;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Cli\Annotation\Command;
use Imi\Cli\Annotation\CommandAction;
use Imi\Core\App\Contract\BaseApp;
use Imi\Event\Event;
use Imi\Util\Process\ProcessAppContexts;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CliApp extends BaseApp
{
    /**
     * @var Application
     */
    protected Application $cli;

    /**
     * @var EventDispatcher
     */
    protected EventDispatcher $cliEventDispatcher;

    /**
     * @var bool
     */
    private bool $initApped = false;

    /**
     * 构造方法.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function __construct(string $namespace)
    {
        parent::__construct($namespace);
        App::set(ProcessAppContexts::SCRIPT_NAME, realpath($_SERVER['SCRIPT_FILENAME']));
        $this->cliEventDispatcher = $dispatcher = new EventDispatcher();
        $this->cli = $cli = new Application('imi', App::getImiVersion());
        $cli->setDispatcher($dispatcher);

        $definition = $cli->getDefinition();
        $definition->addOption(
            new InputOption(
                'app-namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Your project app namespace'
            )
        );
        $definition->addOption(
            new InputOption(
                'imi-runtime',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set imi runtime file',
                null,
            )
        );
        $definition->addOption(
            new InputOption(
                'no-app-cache',
                null,
                InputOption::VALUE_OPTIONAL,
                'Disable app runtime cache',
                false,
            )
        );

        $this->cliEventDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $e) {
            $this->initApp($e->getInput());
        }, \PHP_INT_MAX);
        $this->cliEventDispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $e) {
            $this->onError($e);
        }, \PHP_INT_MAX);

        Event::one('IMI.INITED', function () use ($cli) {
            $this->addCommands();
        });
    }

    private function onError(ConsoleErrorEvent $e): void
    {
        if (!$this->initApped && $e->getError() instanceof CommandNotFoundException)
        {
            $e->stopPropagation();
            // 尝试加载项目
            $this->initApp($e->getInput());
            $this->addCommands();
            $this->run();
        }
    }

    private function addCommands(): void
    {
        foreach (AnnotationManager::getAnnotationPoints(Command::class, 'class') as $point)
        {
            /** @var Command $commandAnnotation */
            $commandAnnotation = $point->getAnnotation();
            $className = $point->getClass();
            foreach (AnnotationManager::getMethodsAnnotations($className, CommandAction::class) as $methodName => $commandActionAnnotations)
            {
                $command = new ImiCommand($commandAnnotation, $commandActionAnnotations[0], $className, $methodName);
                if (!$this->cli->has($command->getName()))
                {
                    $this->cli->add($command);
                }
            }
        }
    }

    private function initApp(Input $input): void
    {
        if (!$this->initApped)
        {
            $this->initApped = true;
            App::initApp((bool) $input->getOption('no-app-cache'));
        }
    }

    /**
     * 获取应用类型.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'cli';
    }

    /**
     * 运行应用.
     *
     * @return void
     */
    public function run(): void
    {
        $this->cli->run(new ImiArgvInput());
    }

    /**
     * Get the value of cli.
     *
     * @return Application
     */
    public function getCli(): Application
    {
        return $this->cli;
    }
}