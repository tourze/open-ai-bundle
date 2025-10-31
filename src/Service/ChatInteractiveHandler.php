<?php

namespace OpenAIBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\Exception\ClientException;

/**
 * 处理交互式聊天的服务类
 */
class ChatInteractiveHandler
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChatInputValidator $inputValidator,
        private readonly ChatResponseHandler $responseHandler,
    ) {
    }

    /**
     * @param array<mixed> $tools
     */
    public function runInteractiveLoop(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper,
        Conversation $conversation,
        Character $character,
        ApiKey $apiKey,
        array $tools,
        bool $isQuiet,
    ): int {
        while (true) {
            $output->writeln('');
            $question = new Question('请输入您的问题 (输入 q 退出, c 清除历史): ');
            $prompt = $helper->ask($input, $output, $question);

            $result = $this->processUserInput($prompt, $output, $conversation, $isQuiet);
            if ('exit' === $result) {
                return Command::SUCCESS;
            }
            if ('continue' === $result) {
                continue;
            }

            // 将用户的消息添加到历史
            $this->conversationService->createUserMessage($conversation, $apiKey, $result);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            $this->handleConversation(
                $input,
                $output,
                $character,
                $apiKey,
                $conversation,
                $tools,
                $isQuiet
            );
        }
    }

    private function processUserInput(string $prompt, OutputInterface $output, Conversation $conversation, bool $isQuiet): string
    {
        $command = $this->inputValidator->checkForCommand($prompt);

        if (null !== $command) {
            return $this->handleUserCommand($command, $output, $conversation, $isQuiet);
        }

        return $this->inputValidator->validateUserPrompt($prompt, $output, $isQuiet);
    }

    private function handleUserCommand(string $command, OutputInterface $output, Conversation $conversation, bool $isQuiet): string
    {
        switch ($command) {
            case 'exit':
                return $this->handleExitCommand($output, $isQuiet);
            case 'clear':
                return $this->handleClearCommand($output, $conversation, $isQuiet);
            default:
                return 'continue';
        }
    }

    private function handleExitCommand(OutputInterface $output, bool $isQuiet): string
    {
        if (!$isQuiet) {
            $output->writeln('再见！');
        }
        $this->entityManager->flush();

        return 'exit';
    }

    private function handleClearCommand(OutputInterface $output, Conversation $conversation, bool $isQuiet): string
    {
        $conversation->clearMessages();
        $this->entityManager->flush();

        if (!$isQuiet) {
            $output->writeln("\n已清除对话历史！");
        }

        return 'continue';
    }

    /**
     * @param array<mixed> $tools
     */
    private function handleConversation(
        InputInterface $input,
        OutputInterface $output,
        Character $character,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $isQuiet,
    ): void {
        try {
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
}
