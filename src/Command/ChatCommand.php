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
            ->setHelp(<<<'HELP'
该命令用于与 AI 模型进行对话。

使用方法:
  <info>php bin/console open-ai:chat -k 1</info> - 使用指定的API密钥
  <info>php bin/console open-ai:chat -c 1</info> - 使用指定的角色
  <info>php bin/console open-ai:chat -m deepseek-chat</info> - 使用指定的模型

示例:
  php bin/console open-ai:chat -k 1 -c 1 -m deepseek-chat
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
        if (!$apiKey) {
            if (!$apiKeyId) {
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

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            // 开启了函数调用，我们才传入这个
            $tools = $apiKey->isFunctionCalling() ? $this->functionService->generateToolsArray($character) : [];

            // 打印当前使用的角色和模型信息
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

            while (true) {
                $output->writeln('');
                $question = new Question('请输入您的问题 (输入 q 退出, c 清除历史): ');
                $prompt = $helper->ask($input, $output, $question);

                if ('q' === $prompt || 'quit' === $prompt || 'exit' === $prompt) {
                    $output->writeln('再见！');
                    $this->entityManager->flush();

                    return Command::SUCCESS;
                }

                if ('c' === $prompt || 'clear' === $prompt) {
                    $conversation->clearMessages();
                    $this->entityManager->flush();

                    $output->writeln("\n已清除对话历史！");
                    continue;
                }

                if (empty($prompt)) {
                    $output->writeln('<error>请提供有效的问题内容</error>');
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
                    );
                } catch (ClientException $e) {
                    $output->writeln(sprintf('<error>对话出错：%s</error>', $e));
                    $output->writeln($e->getResponse()->getContent(false));
                } finally {
                    // 刷新数据到数据库
                    $this->entityManager->flush();
                }
            }

            // return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e));

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
    ): void {
        $options = [
            'debug' => $debug,
            'model' => $apiKey->getModel(),
            'temperature' => $character->getTemperature(),
            'top_p' => $character->getTopP(),
            'max_tokens' => $character->getMaxTokens(),
            'presence_penalty' => $character->getPresencePenalty(),
            'frequency_penalty' => $character->getFrequencyPenalty(),
        ];
        if (!empty($tools)) {
            $options['tools'] = $tools;
        }

        foreach ($this->openAiService->streamReasoner(
            $apiKey,
            $this->conversationService->getMessageArray($conversation),
            $options,
        ) as $chunk) {
            if (!$chunk instanceof StreamChunkVO) {
                // dump($chunk);
                // 输出调试信息
                $output->write($chunk);
                continue;
            }
            // dump($chunk);

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
                $this->fetchResponse($output, $character, $apiKey, $conversation, $tools, $debug);
            }
        }
    }
}
