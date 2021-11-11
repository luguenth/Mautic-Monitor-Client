<?php

namespace App\Command;

use App\Service\GithubApiService;
use App\Service\MauticApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstancesSyncCommand extends Command
{
    protected static $defaultName = 'instances:sync';
    protected static $defaultDescription = 'Add a short description for your command';

    public function __construct(MauticApiService $mauticApi, GithubApiService $githubApi , string $name = null)
    {
        $this->mauticApi = $mauticApi;
        $this->githubApi = $githubApi;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        # sync all instances with the MAuticApiService
        $this->mauticApi->syncInstances($io);
        $io->success('Instances synced');

        dump($this->githubApi->getLatestStableVersion());

        return Command::SUCCESS;
    }
}
