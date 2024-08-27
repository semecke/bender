<?php

namespace App\Service\DiscordEvent;

use App\Service\VoiceStatisticsService;
use Discord\Parts\Channel\Channel;

class ChannelDeleteEvent
{
    public function __construct(private readonly VoiceStatisticsService $voiceStatisticsService)
    {
    }

    public function execute(Channel $channel): void
    {
        if ($channel->type === Channel::TYPE_GUILD_VOICE) {
            $this->voiceStatisticsService->clearAllIncorrectUserStatisticsByChannel($channel);
        }
    }
}