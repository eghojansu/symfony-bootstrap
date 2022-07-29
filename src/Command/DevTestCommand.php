<?php

namespace App\Command;

use App\Extension\Crud\Resource;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dev:test',
    description: 'Test',
)]
class DevTestCommand extends Command
{
    public function __construct(private EntityManagerInterface $db)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        $metas = $this->db->getMetadataFactory()->getAllMetadata();

        $io->writeln(sprintf('Meta data count: %s', count($metas)));

        foreach ($metas as $key => $meta) {
            $attributes = $meta->getReflectionClass()->getAttributes(Resource::class);

            $io->writeln(sprintf('Meta %s: %s, attr: %s', $key, $meta->getName(), count($attributes)));
        }

        return Command::SUCCESS;
    }
}
