<?php

namespace App\Service\DiscordEvent;

use App\Service\BonkService;
use App\Service\VoiceStatisticsService;
use Discord\Parts\Channel\Message;

class MessageCreateEvent
{
    public function __construct(private readonly BonkService            $bonkService,
                                private readonly VoiceStatisticsService $voiceStatisticsService)
    {
    }

    public function execute(Message $message): void
    {
        //                $this->bonkService->execute($message);
        $this->voiceStatisticsService->execute($message);
    }
}