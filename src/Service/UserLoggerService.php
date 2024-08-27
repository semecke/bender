<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\VoiceChannel;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Doctrine\ORM\EntityManagerInterface;

class UserLoggerService
{
    private const LOG_USER_VOICE_CHANNEL_ID = 1275533081025056820;

    public function __construct(private readonly EntityManagerInterface $em,
                                private readonly UserService            $userService,
                                private readonly VoiceChannelService    $voiceChannelService,
                                private readonly DiscordService         $discordService,
                                private readonly MessageService         $messageService
    )
    {
    }

    public function executeVoice(VoiceStateUpdate $state, ?VoiceChannel $currentChannel, ?VoiceChannel $oldChannel): void
    {
        $user = $this->userService->authUser($state->member);
        if ($user === null) {
            return;
        }

        if ($currentChannel !== null && $oldChannel === null) { //юзер зашел в чат
            $this->logUserCameInVoice($user, $currentChannel);
        } elseif ($currentChannel === null && $oldChannel !== null) { //юзер вышел из чата
            $this->logUserCameOutVoice($user, $oldChannel);
        } elseif ($oldChannel !== null & $currentChannel !== null && $currentChannel->getChannelId() !== $oldChannel->getChannelId()) {
            $this->logUserMovedVoice($user, $currentChannel, $oldChannel);
        }
    }

    public function sendMessageAboutUser(string $text): void
    {
        $logChannel = $this->discordService->getDiscord()->getChannel(self::LOG_USER_VOICE_CHANNEL_ID);

        $logChannel->sendMessage($this->messageService->createMessage($text));
    }

    private function logUserCameInVoice(User $user, VoiceChannel $voiceChannel): void
    {
        $this->sendMessageAboutUser(sprintf('<@%s> зашёл в канал <#%s>', $user->getDiscordId(), $voiceChannel->getChannelId()));
    }

    private function logUserCameOutVoice(User $user, VoiceChannel $voiceChannel): void
    {
        $this->sendMessageAboutUser(sprintf('<@%s> покинул канал <#%s>', $user->getDiscordId(), $voiceChannel->getChannelId()));
    }

    private function logUserMovedVoice(User $user, VoiceChannel $currentChannel, VoiceChannel $oldChannel): void
    {
        $this->sendMessageAboutUser(sprintf('<@%s> перешёл из канала <#%s> в канал <#%s>', $user->getDiscordId(), $oldChannel->getChannelId(), $currentChannel->getChannelId()));
    }
}