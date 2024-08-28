<?php

namespace App\Service;

use App\Service\DiscordEvent\ChannelDeleteEvent;
use App\Service\DiscordEvent\ChannelUpdateEvent;
use App\Service\DiscordEvent\GuildMemberUpdateEvent;
use App\Service\DiscordEvent\MessageCreateEvent;
use App\Service\DiscordEvent\PresenceUpdateEvent;
use App\Service\DiscordEvent\VoiceStateUpdateEvent;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event;

class DiscordEventService
{
    public function __construct(private readonly DiscordService         $discordService,
                                private readonly DiscordLogger          $discordLogger,
                                private readonly MessageCreateEvent     $messageCreateEvent,
                                private readonly VoiceStateUpdateEvent  $voiceStateUpdateEvent,
                                private readonly ChannelUpdateEvent     $channelUpdateEvent,
                                private readonly GuildMemberUpdateEvent $guildMemberUpdateEvent,
                                private readonly ChannelDeleteEvent     $channelDeleteEvent,
                                private readonly PresenceUpdateEvent    $presenceUpdateEvent
    )
    {
    }

    /**
     * @throws IntentException
     */
    public function start(): void
    {
        $discord = $this->discordService->createDiscord();
        $this->run($discord);
    }

    private function run(Discord $discord): void
    {
        $discord->on('ready', function (Discord $discord) {
            echo "BOT Bender activated!", PHP_EOL;

            $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
                try {
                    $this->messageCreateEvent->execute($message);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });

            $discord->on(Event::VOICE_STATE_UPDATE, function (VoiceStateUpdate $state, Discord $discord, ?VoiceStateUpdate $oldState) {
                try {
                    $this->voiceStateUpdateEvent->execute($state, $oldState);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });

            $discord->on(Event::CHANNEL_UPDATE, function (Channel $channel, Discord $discord, ?Channel $oldChannel) {
                try {
                    $this->channelUpdateEvent->execute($channel, $oldChannel);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });

            $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $member, Discord $discord, ?Member $oldMember) {
                try {
                    $this->guildMemberUpdateEvent->execute($member, $oldMember);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });

            $discord->on(Event::CHANNEL_DELETE, function (Channel $channel, Discord $discord) {
                try {
                    $this->channelDeleteEvent->execute($channel);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });


            $discord->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presence, Discord $discord) {
                try {
                    $this->presenceUpdateEvent->execute($presence);
                } catch (\Exception $e) {
                    $this->discordLogger->logError($e);
                }
            });
        });

        $discord->run();
    }
}