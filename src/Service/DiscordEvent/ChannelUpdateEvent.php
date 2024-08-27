<?php

namespace App\Service\DiscordEvent;

use Discord\Parts\Channel\Channel;

class ChannelUpdateEvent
{
    public function execute(Channel $channel, ?Channel $oldChannel): void
    {
        //todo отлавливать изменения канала
        // TODO: Implement execute() method.
    }
}