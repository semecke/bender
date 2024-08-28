<?php

namespace App\Service;

class DiscordLogger
{
    private const LOG_ERROR_CHANNEL_ID = 1253631427363340308;

    public function __construct(private DiscordService $discordService, private MessageService $messageService)
    {
    }

    public function logText(string $text, bool $silent = false): void
    {
        $logChannel = $this->discordService->getDiscord()->getChannel(self::LOG_ERROR_CHANNEL_ID);

        $message = $text;
        if ($silent) {
            $message = $this->messageService->createMessage($text);
        }

        $logChannel->sendMessage($message);
    }

    public function logError(\Throwable $e): void
    {
        $logChannel = $this->discordService->getDiscord()->getChannel(self::LOG_ERROR_CHANNEL_ID);

        $logMessageArray = [sprintf('**Error:**  %s', $e->getMessage())];
        $logMessageArray[] = sprintf("%s: %s\n", $e->getLine(), $e->getFile());
        $logMessageArray[] = "**Trace:**";
        $i = 1;
        foreach ($e->getTrace() as $trace) {
            if ($i > 5) {
                break;
            }

            $logMessageArray[] = sprintf('%s: %s', $trace['line'], $trace['file']);
            $i++;
        }

        $logChannel->sendMessage(implode("\n", $logMessageArray));
    }
}