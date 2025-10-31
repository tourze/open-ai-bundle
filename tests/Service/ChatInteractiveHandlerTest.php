<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Service\ChatInputValidator;
use OpenAIBundle\Service\ChatInteractiveHandler;
use OpenAIBundle\Service\ChatResponseHandler;
use OpenAIBundle\Service\ConversationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ChatInteractiveHandler::class)]
#[RunTestsInSeparateProcesses]
final class ChatInteractiveHandlerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    private ChatInteractiveHandler $handler;

    private ConversationService $conversationService;

    private ChatInputValidator $inputValidator;

    private ChatResponseHandler $responseHandler;

    private function setUpTest(): void
    {
        // 创建 mock 服务
        $this->conversationService = $this->createMock(ConversationService::class);
        $this->inputValidator = $this->createMock(ChatInputValidator::class);
        $this->responseHandler = $this->createMock(ChatResponseHandler::class);

        // 将 mock 服务注入到容器中
        self::getContainer()->set(ConversationService::class, $this->conversationService);
        self::getContainer()->set(ChatInputValidator::class, $this->inputValidator);
        self::getContainer()->set(ChatResponseHandler::class, $this->responseHandler);

        // 从容器中获取服务实例
        $this->handler = self::getService(ChatInteractiveHandler::class);
    }

    public function testProcessUserInputExitCommandReturnsExit(): void
    {
        $this->setUpTest();

        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于命令行输出
         * 2. 这种使用是合理和必要的，因为需要测试命令行交互
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为测试中的对话实例
         * 2. 这种使用是合理和必要的，因为需要测试与对话的交互逻辑
         * 3. 暂无更好的替代方案，因为需要作为测试的上下文参数
         */
        $conversation = $this->createMock(Conversation::class);

        $this->inputValidator
            ->expects($this->once())
            ->method('checkForCommand')
            ->with('q')
            ->willReturn('exit')
        ;

        $output->expects($this->once())
            ->method('writeln')
            ->with('再见！')
        ;

        // EntityManager flush 调用会在实际方法中执行，这里不需要验证

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('processUserInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'q', $output, $conversation, false);

        $this->assertEquals('exit', $result);
    }

    public function testProcessUserInputClearCommandReturnsContinue(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于测试清理命令输出
         * 2. 这种使用是合理和必要的，因为需要验证命令行反馈
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，需要测试清理消息的功能
         * 2. 这种使用是合理和必要的，因为需要验证 clearMessages 方法调用
         * 3. 暂无更好的替代方案，因为需要作为测试的上下文参数
         */
        $conversation = $this->createMock(Conversation::class);

        $this->inputValidator
            ->expects($this->once())
            ->method('checkForCommand')
            ->with('c')
            ->willReturn('clear')
        ;

        $conversation
            ->expects($this->once())
            ->method('clearMessages')
        ;

        // EntityManager flush 调用会在实际方法中执行，这里不需要验证

        $output->expects($this->once())
            ->method('writeln')
            ->with("\n已清除对话历史！")
        ;

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('processUserInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'c', $output, $conversation, false);

        $this->assertEquals('continue', $result);
    }

    public function testProcessUserInputNormalPromptReturnsValidatedPrompt(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于测试正常输入处理
         * 2. 这种使用是合理和必要的，因为需要验证正常交互流程
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为测试正常输入处理的上下文
         * 2. 这种使用是合理和必要的，因为需要作为参数传递给被测方法
         * 3. 暂无更好的替代方案，因为是方法的必要参数
         */
        $conversation = $this->createMock(Conversation::class);

        $this->inputValidator
            ->expects($this->once())
            ->method('checkForCommand')
            ->with('Hello World')
            ->willReturn(null)
        ;

        $this->inputValidator
            ->expects($this->once())
            ->method('validateUserPrompt')
            ->with('Hello World', $output, false)
            ->willReturn('Hello World')
        ;

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('processUserInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'Hello World', $output, $conversation, false);

        $this->assertEquals('Hello World', $result);
    }

    public function testHandleExitCommandQuietModeNoOutput(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于测试静默模式下的行为
         * 2. 这种使用是合理和必要的，因为需要验证静默模式下不输出内容
         * 3. 暂无更好的替代方案，因为需要验证 writeln 方法不被调用
         */
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->never())
            ->method('writeln')
        ;

        // EntityManager flush 调用会在实际方法中执行，这里不需要验证

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('handleExitCommand');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, $output, true);

        $this->assertEquals('exit', $result);
    }

    public function testHandleClearCommandQuietModeNoOutput(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于测试清理命令静默模式
         * 2. 这种使用是合理和必要的，因为需要验证静默模式下不输出内容
         * 3. 暂无更好的替代方案，因为需要验证 writeln 方法不被调用
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，需要测试清理功能在静默模式下的行为
         * 2. 这种使用是合理和必要的，因为需要验证 clearMessages 方法调用
         * 3. 暂无更好的替代方案，因为需要作为测试的上下文参数
         */
        $conversation = $this->createMock(Conversation::class);

        $conversation
            ->expects($this->once())
            ->method('clearMessages')
        ;

        // EntityManager flush 调用会在实际方法中执行，这里不需要验证

        $output->expects($this->never())
            ->method('writeln')
        ;

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('handleClearCommand');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, $output, $conversation, true);

        $this->assertEquals('continue', $result);
    }

    public function testHandleConversationCallsResponseHandler(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. InputInterface 是 Symfony Console 的输入接口，用于测试命令行参数
         * 2. 这种使用是合理和必要的，因为需要模拟用户输入参数
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $input = $this->createMock(InputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于测试响应处理流程
         * 2. 这种使用是合理和必要的，因为需要传递给响应处理器
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为对话的角色参数
         * 2. 这种使用是合理和必要的，因为需要传递给响应处理器
         * 3. 暂无更好的替代方案，因为是 fetchResponse 方法的必要参数
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为 API 调用的凭证参数
         * 2. 这种使用是合理和必要的，因为需要传递给响应处理器
         * 3. 暂无更好的替代方案，因为是 fetchResponse 方法的必要参数
         */
        $apiKey = $this->createMock(ApiKey::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为对话上下文参数
         * 2. 这种使用是合理和必要的，因为需要传递给响应处理器
         * 3. 暂无更好的替代方案，因为是 fetchResponse 方法的必要参数
         */
        $conversation = $this->createMock(Conversation::class);
        $tools = [];

        $input->method('getOption')
            ->willReturnMap([
                ['debug', false],
                ['no-stream', false],
            ])
        ;

        $this->responseHandler
            ->expects($this->once())
            ->method('fetchResponse')
            ->with(
                $output,
                $character,
                $apiKey,
                $conversation,
                $tools,
                false, // debug
                false, // no-stream
                false  // isQuiet
            )
        ;

        // EntityManager flush 调用会在实际方法中执行，这里不需要验证

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('handleConversation');
        $method->setAccessible(true);

        $method->invoke(
            $this->handler,
            $input,
            $output,
            $character,
            $apiKey,
            $conversation,
            $tools,
            false
        );
    }

    public function testRunInteractiveLoop(): void
    {
        $this->setUpTest();
        // 由于 runInteractiveLoop 包含无限循环，我们测试其组件方法的集成
        /*
         * 使用具体类进行 mock 的原因：
         * 1. InputInterface 是 Symfony Console 的输入接口，用于模拟交互式输入
         * 2. 这种使用是合理和必要的，因为需要测试命令行交互组件
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $input = $this->createMock(InputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. OutputInterface 是 Symfony Console 的输出接口，用于验证交互式输出
         * 2. 这种使用是合理和必要的，因为需要测试命令行输出逻辑
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准接口
         */
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. QuestionHelper 是 Symfony Console 的问答助手，用于模拟用户输入
         * 2. 这种使用是合理和必要的，因为需要测试交互式问答流程
         * 3. 暂无更好的替代方案，因为这是 Symfony Console 的标准组件
         */
        $helper = $this->createMock(QuestionHelper::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为交互式对话的上下文
         * 2. 这种使用是合理和必要的，因为是交互循环的核心参数
         * 3. 暂无更好的替代方案，因为是方法的必要参数
         */
        $conversation = $this->createMock(Conversation::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为对话角色参数
         * 2. 这种使用是合理和必要的，因为是交互循环的必要参数
         * 3. 暂无更好的替代方案，因为是方法的必要参数
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为 API 调用凭证
         * 2. 这种使用是合理和必要的，因为是交互循环的必要参数
         * 3. 暂无更好的替代方案，因为是方法的必要参数
         */
        $apiKey = $this->createMock(ApiKey::class);

        // 模拟用户输入退出命令来结束循环
        $helper->expects($this->once())
            ->method('ask')
            ->willReturn('q')
        ;

        $this->inputValidator->expects($this->once())
            ->method('checkForCommand')
            ->with('q')
            ->willReturn('exit')
        ;

        // 方法会先输出空行，然后输出退出消息
        $output->expects($this->exactly(2))
            ->method('writeln')
            ->willReturnCallback(function ($message): void {
                static $callCount = 0;
                ++$callCount;
                if (1 === $callCount) {
                    $this->assertEquals('', $message);
                } elseif (2 === $callCount) {
                    $this->assertEquals('再见！', $message);
                }
            })
        ;

        $result = $this->handler->runInteractiveLoop(
            $input,
            $output,
            $helper,
            $conversation,
            $character,
            $apiKey,
            [],
            false
        );

        $this->assertEquals(0, $result); // Command::SUCCESS
    }
}
