<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $discordId;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $username;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $quantityUseBonk = 0;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VoiceStatistics::class)]
    private Collection $voiceStatistics;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $oldNicknames;

    #[ORM\Column(length: 255)]
    private string $nickname;

    public function __construct()
    {
        $this->voiceStatistics = new ArrayCollection();
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getDiscordId(): string
    {
        return $this->discordId;
    }

    public function setDiscordId(string $discordId): self
    {
        $this->discordId = $discordId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getQuantityUseBonk(): int
    {
        return $this->quantityUseBonk;
    }

    public function setQuantityUseBonk(int $quantityUseBonk): self
    {
        $this->quantityUseBonk = $quantityUseBonk;
        return $this;
    }

    /**
     * @return Collection<int, VoiceStatistics>
     */
    public function getVoiceStatistics(): Collection
    {
        return $this->voiceStatistics;
    }

    public function addVoiceStatistic(VoiceStatistics $voiceStatistic): static
    {
        if (!$this->voiceStatistics->contains($voiceStatistic)) {
            $this->voiceStatistics->add($voiceStatistic);
            $voiceStatistic->setUser($this);
        }

        return $this;
    }

    public function removeVoiceStatistic(VoiceStatistics $voiceStatistic): static
    {
        if ($this->voiceStatistics->removeElement($voiceStatistic)) {
            // set the owning side to null (unless already changed)
            if ($voiceStatistic->getUser() === $this) {
                $voiceStatistic->setUser(null);
            }
        }

        return $this;
    }

    public function getOldNicknames(): ?array
    {
        return $this->oldNicknames;
    }

    public function setOldNicknames(?array $oldNicknames): static
    {
        $this->oldNicknames = $oldNicknames;

        return $this;
    }

    public function getNickname(): string
    {
        return $this->shieldingMarkdown($this->nickname);
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    private function shieldingMarkdown(string $value): string
    {
        preg_replace('/\b((https?|ftp|file):\/\/|www\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', ' ', $value);

        $value = str_replace([
            '\\', '-', '#', '*', '+', '`', '.', '[', ']', '(', ')', '!', '&', '<', '>', '_', '{', '}', '|'], [
            '\\\\', '\-', '\#', '\*', '\+', '\`', '\.', '\[', '\]', '\(', '\)', '\!', '\&', '\<', '\>', '\_', '\{', '\}', '\|',
        ], $value);

        return $value;
    }
}