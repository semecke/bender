<?php

namespace App\Service;

use App\Entity\ActivityStatistics;
use App\Entity\User;
use App\Entity\VoiceChannel;
use App\Entity\VoiceStatistics;
use DateTime;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class VoiceStatisticsService
{
    public const ARGUMENT_STATISTICS_MONTH = 'месяц';
    public const ARGUMENT_STATISTICS_WEEK = 'неделя';

    public function __construct(private readonly EntityManagerInterface $em,
                                private readonly UserService            $userService,
                                private readonly VoiceChannelService    $voiceChannelService,
                                private readonly MessageService         $messageService,
                                private CacheInterface                  $cache,
                                private UserLoggerService               $userLoggerService,
                                private DiscordService                  $discordService
    )
    {
    }

    public function execute(Message $message): void
    {
        $user = $this->userService->authUser($message);
        if ($user === null) {
            return;
        }

        if ($this->messageService->isMessageCommand($message, MessageService::COMMAND_STATISTICS_TOP)) {
            $argument = $this->messageService->findCommandArgument($message, MessageService::COMMAND_STATISTICS_TOP);

            switch ($argument) {
                case self::ARGUMENT_STATISTICS_MONTH:
                    $this->showTopForPeriod($message, VoiceStatistics::STATISTICS_PERIOD_MONTH);
                    break;
                case self::ARGUMENT_STATISTICS_WEEK:
                    $this->showTopForPeriod($message, VoiceStatistics::STATISTICS_PERIOD_WEEK);
                    break;
                default:
                    $this->showTopForPeriod($message);
                    break;
            }
            return;
        }

        if ($this->messageService->isMessageCommand($message, MessageService::COMMAND_STATISTICS)) {
            $this->showPersonalStatisticsByUser($message, $user);
            return;
        }
    }

    public function executeVoice(VoiceStateUpdate $state, ?VoiceChannel $currentChannel, ?VoiceChannel $oldChannel): void
    {
        $user = $this->userService->authUser($state->member);
        if ($user === null) {
            return;
        }

        if ($currentChannel !== null && $oldChannel === null) { //юзер зашел в чат
            $this->createStatisticsUserCameInVoice($user, $currentChannel);
        } elseif ($currentChannel === null && $oldChannel !== null) { //юзер вышел из чата
            $this->createStatisticsUserCameOutVoice($user, $oldChannel);
        } elseif ($oldChannel !== null & $currentChannel !== null && $currentChannel->getChannelId() !== $oldChannel->getChannelId()) {
            $this->createStatisticsUserMovedVoice($user, $currentChannel, $oldChannel);
        }
    }

    private function createStatisticsUserCameInVoice(User $user, VoiceChannel $voiceChannel): void
    {
        $this->clearAllIncorrectUserStatisticsByUser($user);

        $statisticsRow = (new VoiceStatistics())
            ->setUser($user)
            ->setVoiceChannel($voiceChannel)
            ->setJoinedAt(new DateTime());

        $this->em->persist($statisticsRow);
        $this->em->flush();
    }

    private function createStatisticsUserCameOutVoice(User $user, VoiceChannel $voiceChannel): void
    {
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);
        $statisticsRow = $voiceStatisticsRepository->findLastOneJoinedStatistics($user, $voiceChannel);

        if (empty($statisticsRow)) {
            return;
        }
        $statisticsRow->setLeavedAt(new DateTime());

        $this->em->persist($statisticsRow);
        $this->em->flush();
    }

    private function createStatisticsUserMovedVoice(User $user, VoiceChannel $currentChannel, VoiceChannel $oldChannel): void
    {
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);

        $statisticsRowOld = $voiceStatisticsRepository->findLastOneJoinedStatistics($user, $oldChannel);
        if (!empty($statisticsRowOld)) {
            $statisticsRowOld->setLeavedAt(new DateTime());
        }

        $statisticsRowCurrent = (new VoiceStatistics())
            ->setUser($user)
            ->setVoiceChannel($currentChannel)
            ->setJoinedAt(new DateTime());

        $this->em->persist($statisticsRowCurrent);
        $this->em->flush();
    }

//погасили все активные статистики юзера
    private function clearAllIncorrectUserStatisticsByUser(User $user): void
    {
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);

        $incorrectStatisticsList = $voiceStatisticsRepository->findActiveStatisticsByUser($user);

        foreach ($incorrectStatisticsList as $incorrectStatistics) {
            $incorrectStatistics->setLeavedAt($incorrectStatistics->getJoinedAt());
        }

        $this->em->flush();
    }

    public function clearAllIncorrectUserStatisticsByChannel(Channel $channel): void
    {
        if ($channel->id === null) {
            return;
        }

        $voiceChannelRepository = $this->em->getRepository(VoiceChannel::class);
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);

        $voiceChannel = $voiceChannelRepository->findOneBy(['channel_id' => $channel->id]);
        if (empty($voiceChannel)) {
            return;
        }

        $incorrectStatisticsList = $voiceStatisticsRepository->findActiveStatisticsByChannel($voiceChannel);

        foreach ($incorrectStatisticsList as $incorrectStatistics) {
            $incorrectStatistics->setLeavedAt($incorrectStatistics->getJoinedAt());
        }

        $this->em->flush();
    }

    private function getBeautifulTime(int $totalTime): string
    {
        $hours = $totalTime / 3600;

        if ($hours < 0.1) {
            $beautifulHours = 0.1;
        } else {
            $beautifulHours = round($hours, 1);
        }

        return $beautifulHours;
    }

    private function showTopForPeriod(Message $message, ?string $period = null): void
    {
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);

        $topStartDate = match ($period) {
            VoiceStatistics::STATISTICS_PERIOD_MONTH => new DateTime('-30day'),
            VoiceStatistics::STATISTICS_PERIOD_WEEK => new DateTime('-7day'),
            default => null,
        };

        $totalTimeTopUsers = $voiceStatisticsRepository->getTopForPeriod(topStartDate: $topStartDate);

        if (empty($totalTimeTopUsers)) {
            return;
        }

        $place = 1;
        $topUserListMessageRows = [];
        foreach ($totalTimeTopUsers as $totalTimeTopUser) {
            $prefix = '';
            if ($place === 1) {
                $prefix = '🥇 ';
            } elseif ($place === 2) {
                $prefix = '🥈 ';

            } elseif ($place === 3) {
                $prefix = '🥉 ';
            }

            $discordId = $totalTimeTopUser['discordId'];
            $totalTime = $this->getBeautifulTime($totalTimeTopUser['totalSeconds']);

            $topUserListMessageRows[] = sprintf('%s. %s <@%s> - %s ч', $place, $prefix, $discordId, $totalTime);

            $place++;
        }

        $messageText = "Топ пользователей в ГС за всё время: \n\n" . implode("\n", $topUserListMessageRows);
        $message->reply($this->messageService->createMessage($messageText));
    }

    private function showPersonalStatisticsByUser(Message $message, User $user): void
    {
        $voiceStatisticsRepository = $this->em->getRepository(VoiceStatistics::class);

        $referencedMessage = $message->referenced_message;

        if (!empty($referencedMessage)) {
            $referencedUser = $this->userService->authUser($referencedMessage);
            if ($referencedUser === null) {
                return;
            }

            $targetUser = $referencedUser;
        } else {
            $targetUser = $user;
        }

        $totalTime = $voiceStatisticsRepository->getPersonalStatisticsForUser($targetUser);

        $replyMessageRows = [sprintf('<@%s> - %s ч', $targetUser->getDiscordId(), $this->getBeautifulTime($totalTime))];

        $friendsTime = $voiceStatisticsRepository->getFriendsTimeByUser($targetUser);
        if (!empty($friendsTime)) {
            $replyMessageRows[] = '';
            $replyMessageRows[] = 'С кем общался дольше всего:';
        }

        $place = 1;
        foreach ($friendsTime as $friend) {
            $discordId = $friend['discordId'];
            $totalTime = $this->getBeautifulTime($friend['totalSeconds']);

            $replyMessageRows[] = sprintf('%s. <@%s> - %s ч', $place, $discordId, $totalTime);

            $place++;
        }

        $activityStatisticsRepository = $this->em->getRepository(ActivityStatistics::class);

        $activityStatistics = $activityStatisticsRepository->getPersonalStatisticsForUser($targetUser);
        if (!empty($activityStatistics)) {
            $replyMessageRows[] = '';
            $replyMessageRows[] = 'Любимые игры:';

            $place = 1;
            foreach ($activityStatistics as $activityStatistic) {
                $activityName = $activityStatistic['activityName'];
                $totalTime = $this->getBeautifulTime($activityStatistic['totalSeconds']);

                $replyMessageRows[] = sprintf('%s. **%s** - %s ч', $place, $activityName, $totalTime);

                $place++;
            }
        }

        $messageText = implode("\n", $replyMessageRows);
        $message->reply($this->messageService->createMessage($messageText));
    }
}