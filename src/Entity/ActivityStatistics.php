<?php

namespace App\Entity;

use App\Repository\ActivityStatisticsRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityStatisticsRepository::class)]
#[ORM\Table(name: 'activity_statistics')]
#[ORM\HasLifecycleCallbacks]
class ActivityStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Activity $activity;

    #[ORM\ManyToOne]
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

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): self
    {
        $this->activity = $activity;
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