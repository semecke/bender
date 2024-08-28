<?php

namespace App\Service\DiscordEvent;

use App\Service\ActivityService;
use Discord\Parts\WebSockets\PresenceUpdate;

class PresenceUpdateEvent
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function execute(PresenceUpdate $presence): void
    {
        $this->activityService->manageStatistics($presence);
    }
}