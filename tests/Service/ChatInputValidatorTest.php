<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ChatInputValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ChatInputValidator::class)]
#[RunTestsInSeparateProcesses]
final class ChatInputValidatorTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    private ChatInputValidator $validator;

    private ApiKeyRepository $apiKeyRepository;

    private CharacterRepository $characterRepository;

    private function setUpTest(): void
    {
        // 创建 mock 仓库
        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);
        $this->characterRepository = $this->createMock(CharacterRepository::class);

        // 将 mock 服务注入到容器中
        self::getContainer()->set(ApiKeyRepository::class, $this->apiKeyRepository);
        self::getContainer()->set(CharacterRepository::class, $this->characterRepository);

        // 从容器中获取服务实例
        $this->validator = self::getService(ChatInputValidator::class);
    }

    public function testResolveCharacterMissingOptionReturnsNull(): void
    {
        $this->setUpTest();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->once())
            ->method('getOption')
            ->with('character')
            ->willReturn(null)
        ;

        $output->expects($this->once())
            ->method('writeln')
            ->with('<error>请使用 -c 或 --character 选项指定角色 ID</error>')
        ;

        $result = $this->validator->resolveCharacter($input, $output);

        $this->assertNull($result);
    }

    public function testResolveCharacterCharacterNotFoundThrowsException(): void
    {
        $this->setUpTest();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->once())
            ->method('getOption')
            ->with('character')
            ->willReturn('123')
        ;

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn(null)
        ;

        $this->expectException(ConfigurationException::class);

        $this->validator->resolveCharacter($input, $output);
    }

    public function testResolveCharacterCharacterNotValidThrowsException(): void
    {
        $this->setUpTest();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为测试无效角色异常的场景
         * 2. 这种使用是合理和必要的，因为需要测试角色的有效性验证
         * 3. 暂无更好的替代方案，因为需要验证 isValid 方法返回 false
         */
        $character = $this->createMock(Character::class);

        $input->expects($this->once())
            ->method('getOption')
            ->with('character')
            ->willReturn('123')
        ;

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn($character)
        ;

        $character->expects($this->once())
            ->method('isValid')
            ->willReturn(false)
        ;

        $this->expectException(ConfigurationException::class);

        $this->validator->resolveCharacter($input, $output);
    }

    public function testResolveCharacterValidCharacterReturnsCharacter(): void
    {
        $this->setUpTest();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为测试有效角色返回的场景
         * 2. 这种使用是合理和必要的，因为需要测试角色的有效性验证
         * 3. 暂无更好的替代方案，因为需要验证 isValid 方法返回 true
         */
        $character = $this->createMock(Character::class);

        $input->expects($this->once())
            ->method('getOption')
            ->with('character')
            ->willReturn('123')
        ;

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn($character)
        ;

        $character->expects($this->once())
            ->method('isValid')
            ->willReturn(true)
        ;

        $result = $this->validator->resolveCharacter($input, $output);

        $this->assertSame($character, $result);
    }

    public function testSanitizeUtf8InputValidStringReturnsTrimmed(): void
    {
        $this->setUpTest();
        $input = '  Hello World  ';
        $result = $this->validator->sanitizeUtf8Input($input);

        $this->assertEquals('Hello World', $result);
    }

    public function testSanitizeUtf8InputInvalidUtf8ReturnsEmpty(): void
    {
        $this->setUpTest();
        $input = "\x80\x81\x82"; // invalid UTF-8
        $result = $this->validator->sanitizeUtf8Input($input);

        $this->assertEquals('', $result);
    }

    public function testCheckForCommandExitCommandReturnsExit(): void
    {
        $this->setUpTest();
        $result = $this->validator->checkForCommand('q');
        $this->assertEquals('exit', $result);

        $result = $this->validator->checkForCommand('quit');
        $this->assertEquals('exit', $result);

        $result = $this->validator->checkForCommand('exit');
        $this->assertEquals('exit', $result);
    }

    public function testCheckForCommandClearCommandReturnsClear(): void
    {
        $this->setUpTest();
        $result = $this->validator->checkForCommand('c');
        $this->assertEquals('clear', $result);

        $result = $this->validator->checkForCommand('clear');
        $this->assertEquals('clear', $result);
    }

    public function testCheckForCommandUnknownCommandReturnsNull(): void
    {
        $this->setUpTest();
        $result = $this->validator->checkForCommand('unknown');
        $this->assertNull($result);
    }

    public function testValidateUserPromptEmptyPromptReturnsContinue(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln')
            ->with('<error>请提供有效的问题内容</error>')
        ;

        $result = $this->validator->validateUserPrompt('', $output, false);

        $this->assertEquals('continue', $result);
    }

    public function testValidateUserPromptValidPromptReturnsSanitized(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->never())
            ->method('writeln')
        ;

        $result = $this->validator->validateUserPrompt('Hello World', $output, false);

        $this->assertEquals('Hello World', $result);
    }

    public function testValidateAndSanitizePromptInvalidCharactersReturnsNull(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln')
            ->with('<error>输入包含无效字符</error>')
        ;

        $result = $this->validator->validateAndSanitizePrompt("\x80\x81", $output, false);

        $this->assertNull($result);
    }

    public function testValidateAndSanitizePromptQuietModeNoOutput(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->never())
            ->method('writeln')
        ;

        $result = $this->validator->validateAndSanitizePrompt("\x80\x81", $output, true);

        $this->assertNull($result);
    }

    public function testResolveApiKey(): void
    {
        $this->setUpTest();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，用于测试 API 密钥解析
         * 2. 这种使用是合理和必要的，因为需要测试角色首选 API 密钥的逻辑
         * 3. 暂无更好的替代方案，因为需要访问角色实体的方法
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，用于模拟有效的 API 密钥
         * 2. 这种使用是合理和必要的，因为测试需要返回具体的 API 密钥对象
         * 3. 暂无更好的替代方案，因为需要验证 API 密钥解析的返回值
         */
        $apiKey = $this->createMock(ApiKey::class);

        // 测试使用角色的首选 API 密钥
        $input->expects($this->once())
            ->method('getOption')
            ->with('api-key')
            ->willReturn('')
        ;

        $character->expects($this->once())
            ->method('getPreferredApiKey')
            ->willReturn($apiKey)
        ;

        $result = $this->validator->resolveApiKey($input, $output, $character);

        $this->assertSame($apiKey, $result);
    }
}
