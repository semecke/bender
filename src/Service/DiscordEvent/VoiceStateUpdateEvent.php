<?php

namespace App\Service\DiscordEvent;

use App\Service\UserLoggerService;
use App\Service\VoiceChannelService;
use App\Service\VoiceStatisticsService;
use Discord\Parts\WebSockets\VoiceStateUpdate;

class VoiceStateUpdateEvent
{
    public function __construct(private readonly VoiceChannelService    $voiceChannelService,
                                private readonly VoiceStatisticsService $voiceStatisticsService,
                                private readonly UserLoggerService      $userLoggerService)
    {
    }

    public function execute(VoiceStateUpdate $state, ?VoiceStateUpdate $oldState): void
    {
        $currentChannel = $state->channel ?? null;
        $oldChannel = $oldState?->channel ?? null;

        $currentVoiceChannel = null;
        if ($currentChannel !== null) {
            $currentVoiceChannel = $this->voiceChannelService->verificationChannel($currentChannel);
        }
        $oldVoiceChannel = null;
        if ($oldChannel !== null) {
            $oldVoiceChannel = $this->voiceChannelService->verificationChannel($oldChannel);
        }

        $this->voiceStatisticsService->executeVoice($state, $currentVoiceChannel, $oldVoiceChannel);
        $this->userLoggerService->executeVoice($state, $currentVoiceChannel, $oldVoiceChannel);
    }
}