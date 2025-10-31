<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 处理聊天命令输入验证和解析的服务类
 */
class ChatInputValidator
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly CharacterRepository $characterRepository,
    ) {
    }

    public function resolveCharacter(InputInterface $input, OutputInterface $output): ?Character
    {
        $characterId = $input->getOption('character');
        if (null === $characterId || '' === $characterId) {
            $output->writeln('<error>请使用 -c 或 --character 选项指定角色 ID</error>');

            return null;
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            throw ConfigurationException::characterNotFound($characterId);
        }

        if (false === $character->isValid()) {
            throw ConfigurationException::characterNotActive($characterId);
        }

        return $character;
    }

    public function resolveApiKey(InputInterface $input, OutputInterface $output, Character $character): ?ApiKey
    {
        $apiKeyId = (string) $input->getOption('api-key');
        $apiKey = $character->getPreferredApiKey();

        if (null === $apiKey) {
            if ('' === $apiKeyId) {
                $output->writeln('<error>请使用 -k 或 --api-key 选项指定 API密钥 ID</error>');

                return null;
            }
            $apiKey = $this->apiKeyRepository->find($apiKeyId);
        }

        if (!$apiKey instanceof ApiKey) {
            throw ConfigurationException::configurationNotFound($apiKeyId);
        }

        return $apiKey;
    }

    public function validateAndSanitizePrompt(string $promptText, OutputInterface $output, bool $isQuiet): ?string
    {
        $sanitized = $this->sanitizeUtf8Input($promptText);

        if ('' === $sanitized) {
            if (!$isQuiet) {
                $output->writeln('<error>输入包含无效字符</error>');
            }

            return null;
        }

        return $sanitized;
    }

    public function sanitizeUtf8Input(string $input): string
    {
        // 首先检查输入是否为有效的 UTF-8
        if (!mb_check_encoding($input, 'UTF-8')) {
            return '';
        }

        // 移除或替换无效的 UTF-8 字符
        $sanitized = mb_convert_encoding($input, 'UTF-8', 'UTF-8');

        // 再次验证转换后的结果
        if (!mb_check_encoding($sanitized, 'UTF-8')) {
            return '';
        }

        // 移除控制字符，但保留换行符和制表符
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized);
        if (null === $sanitized) {
            return '';
        }

        return trim($sanitized);
    }

    public function checkForCommand(string $prompt): ?string
    {
        $exitCommands = ['q', 'quit', 'exit'];
        $clearCommands = ['c', 'clear'];

        if (in_array($prompt, $exitCommands, true)) {
            return 'exit';
        }

        if (in_array($prompt, $clearCommands, true)) {
            return 'clear';
        }

        return null;
    }

    public function validateUserPrompt(string $prompt, OutputInterface $output, bool $isQuiet): string
    {
        if ('' === $prompt) {
            if (!$isQuiet) {
                $output->writeln('<error>请提供有效的问题内容</error>');
            }

            return 'continue';
        }

        $sanitized = $this->sanitizeUtf8Input($prompt);
        if ('' === $sanitized) {
            if (!$isQuiet) {
                $output->writeln('<error>输入包含无效字符，请重新输入</error>');
            }

            return 'continue';
        }

        return $sanitized;
    }
}
