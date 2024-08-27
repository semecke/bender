<?php

namespace App\Service;

use App\Entity\VoiceChannel;
use Discord\Parts\Channel\Channel;
use Doctrine\ORM\EntityManagerInterface;

class VoiceChannelService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    )
    {
    }

    public function verificationChannel(Channel $channel): VoiceChannel
    {
        if ($channel->type !== Channel::TYPE_GUILD_VOICE) {
            throw new \RuntimeException('This channel type is not supported');
        }

        return $this->findOrCreateChannel($channel);
    }

    private function findOrCreateChannel(Channel $channel): VoiceChannel
    {
        $voiceChannelRepository = $this->em->getRepository(VoiceChannel::class);
        $voiceChannel = $voiceChannelRepository->findOneBy(['channel_id' => $channel->id]);

        if ($voiceChannel === null) {
            $voiceChannel = (new VoiceChannel())
                ->setChannelId($channel->id)
                ->setName($channel->name);

            $this->em->persist($voiceChannel);
        }

        $voiceChannel->setName($channel->name);

        $this->em->flush();

        return $voiceChannel;
    }
}