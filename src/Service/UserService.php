<?php

namespace App\Service;

use App\Entity\User;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Discord\Parts\User\User as DiscordUser;

class UserService
{
    private const ADMINS_USERNAMES = ['semecke', 'etosu'];

    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }

    /**
     * @throws \RuntimeException
     */
    public function authUser($object): ?User
    {
        $userRepository = $this->em->getRepository(User::class);

        if ($object instanceof Message) {
            $member = $object->member;
        } elseif ($object instanceof VoiceStateUpdate) {
            $member = $object->member;
        } elseif ($object instanceof Member) {
            $member = $object;
        } else {
            return null;
        }

        if (empty($member)) {
            return null;
        }

        $user = $member->user;

        if ($user === null || $user->bot) {
            return null;
        }

        $userEntity = $userRepository->findOneBy(['discordId' => $user->id]);
        if (empty($userEntity)) {
            $userEntity = (new User())
                ->setDiscordId($user->id)
                ->setUsername($user->username)
                ->setNickname($member->displayname);

            $this->em->persist($userEntity);
        } else {
            $this->updateNickname($member, $userEntity);
        }

        $this->em->flush();

        return $userEntity;
    }

    public function updateNickname(Member $member, User $user): void
    {
        if ($member->displayname !== $user->getNickname()) {
            $oldNickname = $user->getNickname();
            $user->setNickname($member->displayname);

            $oldNicknames = $user->getOldNicknames() ?? [];
            if (!in_array($oldNickname, $oldNicknames, true)) {
                $oldNicknames[] = $oldNickname;
            }
            $user->setOldNicknames($oldNicknames);
        }
    }

    public function isAdmin($userName): bool
    {
        return in_array($userName, self::ADMINS_USERNAMES, true);
    }

    public function isAuthorBot(DiscordUser $author): bool
    {
        return (bool)$author->bot;
    }
}