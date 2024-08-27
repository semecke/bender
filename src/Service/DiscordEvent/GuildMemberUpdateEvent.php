<?php

namespace App\Service\DiscordEvent;

use App\Service\UserService;
use Discord\Parts\User\Member;

class GuildMemberUpdateEvent
{

    public function __construct(private readonly UserService $userService)
    {
    }

    public function execute(Member $member, ?Member $oldMember): void
    {
        if ($oldMember && $member->displayname !== $oldMember->displayname) {
            $this->userService->authUser($member);
        }
    }
}