<?php

namespace App\Service;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\WebSockets\Intents;
use Psr\Log\NullLogger;

class DiscordService
{
    private Discord $discord;

    public function __construct(private readonly string $discordToken,
    )
    {
    }

    public function getDiscord(): Discord
    {
        return $this->discord;
    }

    /**
     * @throws IntentException
     */
    public function createDiscord(): Discord
    {
        if (empty($this->discord)) {
            $this->discord = new Discord([
                'token' => $this->discordToken,
                'intents' => Intents::getAllIntents(),
                'loadAllMembers' => true,
                'logger' => new NullLogger(),
            ]);
        }

        return $this->discord;
    }
}