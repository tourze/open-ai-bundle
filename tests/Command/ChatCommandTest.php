<?php

declare(strict_types=1);

namespace Command;

use OpenAIBundle\Command\ChatCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(ChatCommand::class)]
#[RunTestsInSeparateProcesses]
final class ChatCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(ChatCommand::class);
        $this->assertInstanceOf(ChatCommand::class, $command);

        return new CommandTester($command);
    }

    public function testCommandHasCorrectName(): void
    {
        $command = self::getService(ChatCommand::class);
        $this->assertEquals('open-ai:chat', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(ChatCommand::class);
        $this->assertEquals('调用 AI 模型进行对话', $command->getDescription());
    }

    public function testCommandConfigurationOptions(): void
    {
        $command = self::getService(ChatCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('character'));
        $this->assertTrue($definition->hasOption('api-key'));
        $this->assertTrue($definition->hasOption('debug'));
        $this->assertTrue($definition->hasOption('no-stream'));
        $this->assertTrue($definition->hasOption('prompt'));
        $this->assertTrue($definition->hasOption('quiet'));
    }

    public function testExecuteWithoutRequiredOptions(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([]);

        $this->assertEquals(ChatCommand::FAILURE, $commandTester->getStatusCode());
    }

    public function testOptionCharacter(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--character' => '999999']);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
        $this->assertStringContainsString('角色配置未找到', $commandTester->getDisplay());
    }

    public function testOptionApiKey(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--api-key' => '999999']);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
    }

    public function testOptionDebug(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--debug' => true]);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
    }

    public function testOptionNoStream(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--no-stream' => true]);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
    }

    public function testOptionPrompt(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--prompt' => 'test']);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
    }

    public function testOptionQuiet(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute(['--quiet' => true]);

        $this->assertEquals(ChatCommand::FAILURE, $exitCode);
    }
}
