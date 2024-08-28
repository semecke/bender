<?php

namespace App\Entity;

use App\Repository\VoiceStatisticsRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoiceStatisticsRepository::class)]
#[ORM\Table(name: 'voice_statistics')]
#[ORM\HasLifecycleCallbacks]
class VoiceStatistics
{
    public const STATISTICS_PERIOD_WEEK = 'week';
    public const STATISTICS_PERIOD_MONTH = 'month';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'voiceStatistics', )]
    #[ORM\JoinColumn(nullable: false)]
    private VoiceChannel $voiceChannel;

    #[ORM\ManyToOne(inversedBy: 'voiceStatistics')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private DateTime $joinedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $leavedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getVoiceChannel(): VoiceChannel
    {
        return $this->voiceChannel;
    }

    public function setVoiceChannel(VoiceChannel $voiceChannel): self
    {
        $this->voiceChannel = $voiceChannel;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getJoinedAt(): DateTime
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(DateTime $joinedAt): self
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLeavedAt(): ?DateTime
    {
        return $this->leavedAt;
    }

    public function setLeavedAt(?DateTime $leavedAt): self
    {
        $this->leavedAt = $leavedAt;
        return $this;
    }
}