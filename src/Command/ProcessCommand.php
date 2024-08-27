<?php

namespace App\Command;

use App\Service\DiscordEventService;
use Discord\Exceptions\IntentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:process')]
class ProcessCommand extends Command
{

    public function __construct(private readonly DiscordEventService $discordService
    )
    {
        parent::__construct();
    }

    /**
     * @throws IntentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->discordService->start();

        return Command::SUCCESS;
    }
}