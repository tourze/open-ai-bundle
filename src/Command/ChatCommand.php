<?php

namespace OpenAIBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamRequestOptions;
use OpenAIBundle\VO\StreamResponseVO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\Exception\ClientException;

#[AsCommand(
    name: self::NAME,
    description: '调用 AI 模型进行对话',
)]
class ChatCommand extends Command
{
    public const NAME = 'open-ai:chat';
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ConversationService $conversationService,
        private readonly CharacterRepository $characterRepository,
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
            ->setHelp(<<<'HELP'
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
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $characterId = $input->getOption('character');
        if (empty($characterId)) {
            $output->writeln('<error>请使用 -c 或 --character 选项指定角色 ID</error>');

            return Command::FAILURE;
        }
        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            throw ConfigurationException::characterNotFound($characterId);
        }
        if (!$character->isValid()) {
            throw ConfigurationException::characterNotActive($characterId);
        }

        $apiKeyId = (string)$input->getOption('api-key');
        $apiKey = $character->getPreferredApiKey();
        if (null === $apiKey) {
            if ('' === $apiKeyId) {
                $output->writeln('<error>请使用 -k 或 --api-key 选项指定 API密钥 ID</error>');

                return Command::FAILURE;
            }
            $apiKey = $this->apiKeyRepository->find($apiKeyId);
        }
        if (!$apiKey instanceof ApiKey) {
            throw ConfigurationException::configurationNotFound($apiKeyId);
        }

        try {
            $conversation = $this->conversationService->initConversation($character, $apiKey);

            // 开启了函数调用，我们才传入这个
            $tools = $apiKey->isFunctionCalling() ? $this->functionService->generateToolsArray($character) : [];

            $promptText = $input->getOption('prompt');
            $isQuiet = (bool) $input->getOption('quiet');

            // 单次执行模式
            if (!empty($promptText)) {
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

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            // 交互模式的信息输出
            if (!$isQuiet) {
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

            while (true) {
                $output->writeln('');
                $question = new Question('请输入您的问题 (输入 q 退出, c 清除历史): ');
                $prompt = $helper->ask($input, $output, $question);

                if ('q' === $prompt || 'quit' === $prompt || 'exit' === $prompt) {
                    if (!$isQuiet) {
                        $output->writeln('再见！');
                    }
                    $this->entityManager->flush();

                    return Command::SUCCESS;
                }

                if ('c' === $prompt || 'clear' === $prompt) {
                    $conversation->clearMessages();
                    $this->entityManager->flush();

                    if (!$isQuiet) {
                        $output->writeln("\n已清除对话历史！");
                    }
                    continue;
                }

                if (empty($prompt)) {
                    if (!$isQuiet) {
                        $output->writeln('<error>请提供有效的问题内容</error>');
                    }
                    continue;
                }

                // 清理和验证用户输入的 UTF-8 编码
                $prompt = $this->sanitizeUtf8Input($prompt);
                if (empty($prompt)) {
                    if (!$isQuiet) {
                        $output->writeln('<error>输入包含无效字符，请重新输入</error>');
                    }
                    continue;
                }

                // 将用户的消息添加到历史
                $this->conversationService->createUserMessage($conversation, $apiKey, $prompt);

                $this->entityManager->persist($conversation);
                $this->entityManager->flush();

                try {
                    $this->fetchResponse(
                        $output,
                        $character,
                        $apiKey,
                        $conversation,
                        $tools,
                        (bool) $input->getOption('debug'),
                        (bool) $input->getOption('no-stream'),
                        $isQuiet
                    );
                } catch (ClientException $e) {
                    if (!$isQuiet) {
                        $output->writeln(sprintf('<error>对话出错：%s</error>', $e));
                        $output->writeln($e->getResponse()->getContent(false));
                    }
                } finally {
                    // 刷新数据到数据库
                    $this->entityManager->flush();
                }
            }

            // return Command::SUCCESS;
        } catch (\Throwable $e) {
            $isQuiet = (bool) $input->getOption('quiet');
            if (!$isQuiet) {
                $output->writeln(sprintf('<error>%s</error>', $e));
            }

            return Command::FAILURE;
        }
    }

    private function fetchResponse(
        OutputInterface $output,
        Character $character,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $debug,
        bool $noStream = false,
        bool $isQuiet = false,
    ): void {
        $options = new StreamRequestOptions(
            debug: $debug,
            model: $apiKey->getModel(),
            temperature: $character->getTemperature(),
            topP: $character->getTopP(),
            maxTokens: $character->getMaxTokens(),
            presencePenalty: $character->getPresencePenalty(),
            frequencyPenalty: $character->getFrequencyPenalty(),
            tools: !empty($tools) ? $tools : null,
        );

        if ($noStream) {
            // 非流式模式
            $response = $this->openAiService->chat(
                $apiKey,
                $this->conversationService->getMessageArray($conversation),
                $options,
            );
            
            $this->processNonStreamResponse($output, $conversation, $apiKey, $response, $tools, $debug, $isQuiet);
        } else {
            // 流式模式
            foreach ($this->openAiService->streamReasoner(
                $apiKey,
                $this->conversationService->getMessageArray($conversation),
                $options,
            ) as $chunk) {
            $tryNow = false;

            foreach ($chunk->getChoices() as $choice) {
                // 输出内容
                if (null !== $choice->getContent()) {
                    $this->conversationService->appendAssistantContent(
                        $conversation,
                        $apiKey,
                        $chunk->getMsgId(),
                        strval($choice->getContent()),
                    );
                    if (strlen($choice->getContent()) > 0) {
                        $output->write($choice->getContent());
                    }
                }
                // 思维链内容
                if (null !== $choice->getReasoningContent()) {
                    $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $chunk, $choice);
                    if (strlen($choice->getReasoningContent()) > 0) {
                        $output->write("<comment>{$choice->getReasoningContent()}</comment>");
                    }
                }

                // 函数请求
                if (!empty($choice->getToolCalls())) {
                    $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $chunk, $choice);
                    foreach ($choice->getDecodeToolCalls() as $toolCall) {
                        // 参考 https://api-docs.deepseek.com/zh-cn/guides/function_calling
                        // dump($toolCall);

                        // $output->write("<comment>{$toolCall->getFunctionName()}执行中...</comment>");

                        $this->conversationService->createToolMessage(
                            $conversation,
                            $apiKey,
                            $this->functionService->invoke($toolCall),
                            $toolCall->getId(),
                        );
                    }
                    $tryNow = true;
                }
            }

            // 使用情况
            $usage = $chunk->getUsage();
            if (null !== $usage) {
                //                if ($debug) {
                //                    $output->writeln(sprintf(
                //                        "\n<comment>使用情况：提示词 %d 个令牌，完成 %d 个令牌，总共 %d 个令牌</comment>",
                //                        $usage->getPromptTokens(),
                //                        $usage->getCompletionTokens(),
                //                        $usage->getTotalTokens()
                //                    ));
                //                }
                $this->conversationService->appendAssistantUsage($conversation, $apiKey, $chunk, $usage);
            }

                if ($tryNow) {
                    // 重新请求
                    $this->fetchResponse($output, $character, $apiKey, $conversation, $tools, $debug, false, $isQuiet);
                }
            }
        }
    }

    private function processNonStreamResponse(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        array $tools,
        bool $debug,
        bool $isQuiet = false,
    ): void {
        foreach ($response->choices as $choice) {
            // 输出内容
            if (null !== $choice->getContent()) {
                $this->conversationService->appendAssistantContent(
                    $conversation,
                    $apiKey,
                    $response->getMsgId(),
                    strval($choice->getContent()),
                );
                if (strlen($choice->getContent()) > 0) {
                    $output->write($choice->getContent());
                }
            }

            // 思维链内容（非流式模式通常不会有这个，但保持一致性）
            if (null !== $choice->getReasoningContent()) {
                // 为非流式模式创建一个临时的 StreamChunkVO
                $tempChunk = new StreamChunkVO(
                    $response->id,
                    $response->created,
                    $response->model,
                    $response->systemFingerprint,
                    $response->object,
                    [$choice],
                    $response->usage
                );
                $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $tempChunk, $choice);
                if (strlen($choice->getReasoningContent()) > 0) {
                    $output->write("<comment>{$choice->getReasoningContent()}</comment>");
                }
            }

            // 函数请求
            if (!empty($choice->getToolCalls())) {
                // 为非流式模式创建一个临时的 StreamChunkVO
                $tempChunk = new StreamChunkVO(
                    $response->id,
                    $response->created,
                    $response->model,
                    $response->systemFingerprint,
                    $response->object,
                    [$choice],
                    $response->usage
                );
                $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $tempChunk, $choice);
                foreach ($choice->getDecodeToolCalls() as $toolCall) {
                    $this->conversationService->createToolMessage(
                        $conversation,
                        $apiKey,
                        $this->functionService->invoke($toolCall),
                        $toolCall->getId(),
                    );
                }
                // 重新请求处理函数调用结果
                $this->fetchResponse($output, $conversation->getActor(), $apiKey, $conversation, $tools, $debug, true, $isQuiet);
            }
        }

        // 使用情况
        $usage = $response->usage;
        if (null !== $usage) {
            // 为非流式模式创建一个临时的 StreamChunkVO
            $tempChunk = new StreamChunkVO(
                $response->id,
                $response->created,
                $response->model,
                $response->systemFingerprint,
                $response->object,
                $response->choices,
                $response->usage
            );
            $this->conversationService->appendAssistantUsage($conversation, $apiKey, $tempChunk, $usage);
        }
    }

    private function executeSinglePrompt(
        InputInterface $input,
        OutputInterface $output,
        Conversation $conversation,
        Character $character,
        ApiKey $apiKey,
        array $tools,
        string $promptText,
        bool $isQuiet
    ): int {
        // 清理和验证用户输入的 UTF-8 编码
        $promptText = $this->sanitizeUtf8Input($promptText);
        if (empty($promptText)) {
            if (!$isQuiet) {
                $output->writeln('<error>输入包含无效字符</error>');
            }
            return Command::FAILURE;
        }

        try {
            // 将用户的消息添加到历史
            $this->conversationService->createUserMessage($conversation, $apiKey, $promptText);

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            // 执行对话
            $this->fetchResponse(
                $output,
                $character,
                $apiKey,
                $conversation,
                $tools,
                (bool) $input->getOption('debug'),
                (bool) $input->getOption('no-stream'),
                $isQuiet
            );

            // 刷新数据到数据库
            $this->entityManager->flush();

            return Command::SUCCESS;
        } catch (ClientException $e) {
            if (!$isQuiet) {
                $output->writeln(sprintf('<error>对话出错：%s</error>', $e));
                $output->writeln($e->getResponse()->getContent(false));
            }
            return Command::FAILURE;
        } catch (\Throwable $e) {
            if (!$isQuiet) {
                $output->writeln(sprintf('<error>%s</error>', $e));
            }
            return Command::FAILURE;
        }
    }

    private function sanitizeUtf8Input(string $input): string
    {
        // 移除或替换无效的 UTF-8 字符
        $sanitized = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        
        // 进一步验证和清理
        if (!mb_check_encoding($sanitized, 'UTF-8')) {
            return '';
        }
        
        // 移除控制字符，但保留换行符和制表符
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized);
        
        return trim($sanitized);
    }
}
