<?php

namespace App\Service;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Doctrine\ORM\EntityManagerInterface;

class BonkService
{
    const GIF_BONK = 'https://cdn.discordapp.com/attachments/1253631427363340308/1275188322062434355/going-crazy-willem-dafoe.mp4?ex=66c4fb2b&is=66c3a9ab&hm=6cc4d3ed58bf407e5bf96810bad922ed7f2729893559e2d2b9812e3da0a677c7&';

    public function __construct(private readonly EntityManagerInterface $em, private readonly UserService $userService)
    {
    }

    public function execute(Message $message): void
    {
        if ($message->referenced_message === null ||
            $message->referenced_message->author === null ||
            $message->referenced_message->author->bot ||
            mb_strtolower(trim($message->content)) !== 'бонк' ||
            $message->referenced_message?->author->username === $message->member->username
        ) {
            return;
        }

        $user = $this->userService->authUser($message);
        $referencedUser = $this->userService->authUser($message->referenced_message);

        if ($this->userService->isAdmin($message->referenced_message?->author->username)) {
            $message->reply('Аска попросил послать тебя нахуй! Иди нахуй!');
        } else {
            //todo создавать сообщение без упоминаний (пример в MessageService)
            $message->referenced_message->reply(MessageBuilder::new()
                ->addAttachment(self::GIF_BONK, sprintf('**%s** пнул **%s**', $user->getNickname(), $referencedUser->getNickname()))
            );
        }

        $user->setQuantityUseBonk($user->getQuantityUseBonk() + 1);
        $this->em->flush();
    }
}