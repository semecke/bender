<?php

namespace App\Service;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;

class MessageService
{
    private const COMMAND_SYMBOL = '!';
    public const COMMAND_STATISTICS_TOP = 'топ';
    public const COMMAND_STATISTICS = 'стата';

    public function isMessageCommand(Message $message, string $command): bool
    {
        $content = $message->content;
        if (empty($content)) {
            return false;
        }

        $messageContent = trim($content);

        if (mb_strpos($messageContent, self::COMMAND_SYMBOL . $command) === 0) {
            return true;
        }

        return false;
    }

    public function findCommandArgument(Message $message, string $command): ?string
    {
        if (!$this->isMessageCommand($message, $command)) {
            throw new \RuntimeException('This message not command');
        }

        $messageContent = trim($message->content);

        $argumentString = str_replace(self::COMMAND_SYMBOL . $command, '', $messageContent);
        $argumentString = str_replace(' ', '', $argumentString);

        return $argumentString;
    }

    public function createMessage(string $content)
    {
        return MessageBuilder::new()
            ->setContent($content)
            ->setAllowedMentions(['parse' => []]);
    }
}