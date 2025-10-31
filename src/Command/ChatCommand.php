<?php

namespace OpenAIBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Service\ChatInputValidator;
use OpenAIBundle\Service\ChatInteractiveHandler;
use OpenAIBundle\Service\ChatResponseHandler;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\Exception\ClientException;

#[AsCommand(name: self::NAME, description: '调用 AI 模型进行对话', help: <<<'TXT'
    该命令用于与 AI 模型进行对话。支持交互模式和单次执行模式。

    使用方法:
      <info>php bin/console open-ai:chat -c 1</info> - 交互模式
      <info>php bin/console open-ai:chat -c 1 -s</info> - 交互模式，使用非流式响应
      <info>php bin/console open-ai:chat -c 1 -p "写一首诗"</info> - 单次执行模式
      <info>php bin/console open-ai:chat -c 1 -p "写一首诗" -q</info> - 单次执行，静默模式
      <info>php bin/console open-ai:chat -c 1 -p "写一首诗" -s -q</info> - 单次执行，非流式，静默模式

    参数说明:
      -c, --character  角色ID（必选）
      -k, --api-key    API密钥ID（可选，优先使用角色的默认密钥）
      -p, --prompt     直接指定提示词，启用单次执行模式
      -s, --no-stream  使用非流式模式（默认为流式）
      -q, --quiet      静默模式，只输出AI响应内容，无其他信息
      -d, --debug      开启调试模式

    模式说明:
      - 交互模式：持续对话，支持历史记录和清除
      - 单次执行：指定prompt后立即执行并退出，适合脚本调用
      - 静默模式：仅输出AI响应，无额外信息，适合管道操作
    TXT)]
class ChatCommand extends Command
{
    public const NAME = 'open-ai:chat';

    public function __construct(
        private readonly ChatInputValidator $inputValidator,
        private readonly ChatResponseHandler $responseHandler,
        private readonly ChatInteractiveHandler $interactiveHandler,
        private readonly ConversationService $conversationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly FunctionService $functionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'character',
                'c',
                InputOption::VALUE_REQUIRED,
                '角色 ID'
            )
            ->addOption(
                'api-key',
                'k',
                InputOption::VALUE_REQUIRED,
                'API密钥 ID'
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                '开启调试模式'
            )
            ->addOption(
                'no-stream',
                's',
                InputOption::VALUE_NONE,
                '使用非流式模式（默认为流式）'
            )
            ->addOption(
                'prompt',
                'p',
                InputOption::VALUE_REQUIRED,
                '直接指定提示词，单次执行模式'
            )
            ->addOption(
                'quiet',
                'q',
                InputOption::VALUE_NONE,
                '静默模式，只输出AI响应内容'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $character = $this->inputValidator->resolveCharacter($input, $output);
            if (!$character instanceof Character) {
                return Command::FAILURE;
            }

            $apiKey = $this->inputValidator->resolveApiKey($input, $output, $character);
            if (!$apiKey instanceof ApiKey) {
                return Command::FAILURE;
            }
            $conversation = $this->conversationService->initConversation($character, $apiKey);

            // 开启了函数调用，我们才传入这个
            $tools = true === $apiKey->isFunctionCalling() ? $this->functionService->generateToolsArray($character) : [];

            $promptText = $input->getOption('prompt');
            $isQuiet = (bool) $input->getOption('quiet');

            // 单次执行模式
            if (null !== $promptText && '' !== $promptText) {
                return $this->executeSinglePrompt(
                    $input,
                    $output,
                    $conversation,
                    $character,
                    $apiKey,
                    $tools,
                    $promptText,
                    $isQuiet
                );
            }

            $helper = $this->getHelper('question');
            assert($helper instanceof QuestionHelper);

            // 交互模式的信息输出
            if (!$isQuiet) {
                $this->displaySessionInfo($output, $character, $apiKey);
            }

            return $this->interactiveHandler->runInteractiveLoop(
                $input,
                $output,
                $helper,
                $conversation,
                $character,
                $apiKey,
                $tools,
                $isQuiet
            );
        } catch (\Throwable $e) {
            $isQuiet = (bool) $input->getOption('quiet');
            if (!$isQuiet) {
                $output->writeln(sprintf('<error>%s</error>', $e));
            }

            return Command::FAILURE;
        }
    }

    private function displaySessionInfo(OutputInterface $output, Character $character, ApiKey $apiKey): void
    {
        $output->writeln(sprintf(
            '<comment>当前使用角色：%s</comment>',
            $character->getName()
        ));
        $output->writeln(sprintf(
            '<comment>当前使用密钥：%s</comment>',
            $apiKey->getTitle(),
        ));
        $output->write(sprintf(
            '<comment>当前使用模型：%s</comment>',
            $apiKey->getModel(),
        ));
        $output->writeln('');
    }

    /**
     * @param array<string, mixed> $tools
     */
    private function executeSinglePrompt(
        InputInterface $input,
        OutputInterface $output,
        Conversation $conversation,
        Character $character,
        ApiKey $apiKey,
        array $tools,
        string $promptText,
        bool $isQuiet,
    ): int {
        $sanitizedText = $this->inputValidator->validateAndSanitizePrompt($promptText, $output, $isQuiet);
        if (null === $sanitizedText) {
            return Command::FAILURE;
        }

        try {
            $this->saveUserMessage($conversation, $apiKey, $sanitizedText);
            $this->executeConversation($input, $output, $character, $apiKey, $conversation, $tools, $isQuiet);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            return $this->handlePromptExecutionError($e, $output, $isQuiet);
        }
    }

    private function saveUserMessage(Conversation $conversation, ApiKey $apiKey, string $promptText): void
    {
        $this->conversationService->createUserMessage($conversation, $apiKey, $promptText);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $tools
     */
    private function executeConversation(
        InputInterface $input,
        OutputInterface $output,
        Character $character,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $isQuiet,
    ): void {
        $this->responseHandler->fetchResponse(
            $output,
            $character,
            $apiKey,
            $conversation,
            $tools,
            (bool) $input->getOption('debug'),
            (bool) $input->getOption('no-stream'),
            $isQuiet
        );

        $this->entityManager->flush();
    }

    private function handlePromptExecutionError(\Throwable $e, OutputInterface $output, bool $isQuiet): int
    {
        if (!$isQuiet) {
            if ($e instanceof ClientException) {
                $output->writeln(sprintf('<error>对话出错：%s</error>', $e));
                $output->writeln($e->getResponse()->getContent(false));
            } else {
                $output->writeln(sprintf('<error>%s</error>', $e));
            }
        }

        return Command::FAILURE;
    }
}
