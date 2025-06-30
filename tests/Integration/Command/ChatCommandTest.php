<?php

namespace OpenAIBundle\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Command\ChatCommand;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\Service\OpenAiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ChatCommandTest extends TestCase
{
    private ChatCommand $command;
    private OpenAiService $openAiService;
    private ApiKeyRepository $apiKeyRepository;
    private ConversationService $conversationService;
    private CharacterRepository $characterRepository;
    private EntityManagerInterface $entityManager;
    private FunctionService $functionService;

    protected function setUp(): void
    {
        $this->openAiService = $this->createMock(OpenAiService::class);
        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);
        $this->conversationService = $this->createMock(ConversationService::class);
        $this->characterRepository = $this->createMock(CharacterRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->functionService = $this->createMock(FunctionService::class);

        $this->command = new ChatCommand(
            $this->openAiService,
            $this->apiKeyRepository,
            $this->conversationService,
            $this->characterRepository,
            $this->entityManager,
            $this->functionService
        );
    }

    public function testCommandHasCorrectName(): void
    {
        $this->assertEquals('open-ai:chat', $this->command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $this->assertEquals('调用 AI 模型进行对话', $this->command->getDescription());
    }

    public function testExecuteWithoutCharacterOption(): void
    {
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('open-ai:chat');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals(ChatCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('请使用 -c 或 --character 选项指定角色 ID', $commandTester->getDisplay());
    }

    public function testExecuteWithInvalidCharacter(): void
    {
        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('open-ai:chat');
        $commandTester = new CommandTester($command);

        $this->expectException(\OpenAIBundle\Exception\ConfigurationException::class);

        $commandTester->execute([
            '--character' => '999',
        ]);
    }

    public function testExecuteWithValidCharacterButNoApiKey(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $character->expects($this->once())
            ->method('getPreferredApiKey')
            ->willReturn(null);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($character);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('open-ai:chat');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--character' => '1',
        ]);

        $this->assertEquals(ChatCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('请使用 -k 或 --api-key 选项指定 API密钥 ID', $commandTester->getDisplay());
    }

    public function testExecuteWithValidCharacterAndApiKey(): void
    {
        $character = $this->createMock(Character::class);
        $character->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        $apiKey = $this->createMock(ApiKey::class);
        $apiKey->expects($this->once())
            ->method('isFunctionCalling')
            ->willReturn(true);
            
        $character->expects($this->once())
            ->method('getPreferredApiKey')
            ->willReturn($apiKey);

        $conversation = $this->createMock(Conversation::class);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($character);

        $this->conversationService->expects($this->once())
            ->method('initConversation')
            ->with($character, $apiKey)
            ->willReturn($conversation);

        $this->conversationService->expects($this->once())
            ->method('createUserMessage')
            ->with($conversation, $apiKey, 'Hello');

        $this->functionService->expects($this->once())
            ->method('generateToolsArray')
            ->with($character)
            ->willReturn([]);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('open-ai:chat');
        $commandTester = new CommandTester($command);

        // 使用单次执行模式避免交互
        $commandTester->execute([
            '--character' => '1',
            '--prompt' => 'Hello',
        ]);

        // 由于涉及复杂的流式处理，我们主要测试命令能够正确初始化
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testCommandConfigurationOptions(): void
    {
        $definition = $this->command->getDefinition();
        
        $this->assertTrue($definition->hasOption('character'));
        $this->assertTrue($definition->hasOption('api-key'));
        $this->assertTrue($definition->hasOption('debug'));
        $this->assertTrue($definition->hasOption('no-stream'));
        $this->assertTrue($definition->hasOption('prompt'));
        $this->assertTrue($definition->hasOption('quiet'));
    }
}