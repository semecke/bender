<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\ActivityStatistics;
use App\Entity\User;
use Discord\Parts\User\Activity as DiscordActivity;
use Discord\Parts\WebSockets\PresenceUpdate;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{

    public function __construct(private readonly UserService            $userService,
                                private readonly EntityManagerInterface $em,
                                private readonly UserLoggerService      $userLoggerService,
                                private DiscordLogger                   $discordLogger)
    {
    }


    public function manageStatistics(PresenceUpdate $presence): void
    {
        $user = $this->userService->authUser($presence);
        if ($user === null) {
            return;
        }

        $game = $presence->game;

        $activityStatisticsRepository = $this->em->getRepository(ActivityStatistics::class);
        $statisticsRow = $activityStatisticsRepository->findLastOneJoinedStatistics($user);

        if ($statisticsRow !== null && $game === null) { // вышёл из игры
            $statisticsRow->setLeavedAt(new \DateTime());

            $this->em->flush();
        } elseif ($statisticsRow !== null && $game !== null && $statisticsRow->getActivity()->getApplicationId() !== $game->application_id) { // перешёл из игры в игру
            $statisticsRow->setLeavedAt(new \DateTime());

            $this->createStatistics($user, $game);
        } elseif ($game !== null) { // зашёл в игру
            $this->createStatistics($user, $game);
        }
    }

    private function findOrCreateActivity(DiscordActivity $activityDiscord): Activity
    {
        $repository = $this->em->getRepository(Activity::class);
        $activity = $repository->findOneBy(['applicationId' => $activityDiscord->application_id]);

        if ($activity === null) {
            $activity = (new Activity())
                ->setApplicationId($activityDiscord->application_id)
                ->setName($activityDiscord->name);

            $this->em->persist($activity);
        }

        $activity->setName($activityDiscord->name);
        $this->em->flush();

        return $activity;
    }

    private function createStatistics(User $user, DiscordActivity $game): void
    {
        $activity = $this->findOrCreateActivity($game);

        $newStatisticsRow = (new ActivityStatistics())
            ->setJoinedAt(new \DateTime())
            ->setUser($user)
            ->setActivity($activity);

        $this->em->persist($newStatisticsRow);
        $this->em->flush();
    }

}