<?php

namespace App\Command;

use App\Extension\Utils;
use Doctrine\DBAL\Connection;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'dev:lazy',
    description: 'Lazy developer!',
)]
class DevLazyCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('groups', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'Action group to execute')
            ->addOption('excludes', 'x', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Excluded actions')
            ->addOption('dry', 'd', InputOption::VALUE_NONE, 'Show steps only')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $check = $io->askHidden('What?');

        if ('dev' !== $this->projectEnv || $check !== $this->devPass) {
            $io->warning('Please stop! Just stop...');

            return Command::SUCCESS;
        }

        $time = new Timer();
        $time->start();

        $excludes = self::quote($input->getOption('excludes'));
        $groups = self::quote($input->getArgument('groups'));
        $actions = $this->getActions($groups, $excludes);

        if ($input->getOption('dry')) {
            $output->writeln('Available actions:');

            array_walk($actions, static fn (string ...$args) => (
                $output->writeln(sprintf('  - <info>%s</>', $args[1]))
            ));

            if (!$actions) {
                $output->writeln('  <comment>None</>');
            }

            return self::SUCCESS;
        }

        $executed = 0;
        $success = null;
        $total = count($actions);
        $run = fn ($method) => match($method) {
            '_01Seed01Seed' => $this->_01Seed01Seed($output),
            '_01Import01Support' => $this->_01Import01Support($output, $time),
            default => false,
        } ?? true;
        $separate = static fn () => $output->writeln(str_repeat('-', 50));

        array_walk(
            $actions,
            static function (
                string $method,
                string $action,
            ) use (
                &$success,
                &$executed,
                $total,
                $run,
                $time,
                $io,
                $separate,
            ) {
                if (false === $success) {
                    return;
                }

                $time->start();

                $separate();
                $io->writeln(sprintf(
                    'Running <comment>%s</> (<comment>%s</>/<comment>%s</>)',
                    $action,
                    ++$executed,
                    $total,
                ));

                $success = $run($method);
                $elapsed = $time->stop()->asString();

                $io->newLine();
                $io->writeln(sprintf(
                    '-- End running <comment>%s</> (<comment>%s</>)',
                    $action,
                    $elapsed,
                ));
            },
        );

        list($tag, $message) = match($success) {
            true => array('info', 'Successful'),
            false => array('error', 'Failed'),
            default => array('comment', 'None executed'),
        };

        $io->newLine();
        $io->writeln(sprintf(
            'Action performed: <comment>%s</>/<comment>%s</>',
            $executed,
            $total,
        ));
        $io->writeln(sprintf(
            'Elapsed: <comment>%s</>',
            $time->stop()->asString(),
        ));
        $io->writeln(sprintf(
            'Summary Result: <%s>%s</>',
            $tag,
            $message,
        ));

        return Command::SUCCESS;
    }

    private static function quote(array $values): string
    {
        return $values ? '(' . implode('|', array_map(
            static fn (string $val) => preg_quote($val, '/'),
            $values,
        )) . ')' : '';
    }

    private function getActions(string $groups, string $excludes): array
    {
        $start = '/^_(\d+)';
        $end = '/i';
        $includes = $groups ? $start . $groups . $end : '';
        $ignores = $excludes ? $start . $groups . $excludes . '$' . $end : '';
        $methods = get_class_methods($this);
        $actions = $includes ? (
            ($founds = preg_grep($includes, $methods)) && $ignores ?
                array_filter(
                    $founds,
                    static fn (string $method) => !preg_match(
                        $ignores,
                        $method,
                    ),
                ) :
                $founds
        ) : array();

        sort($actions);

        return array_combine(
            array_map(
                static fn (string $action) => preg_replace(
                    $start . $groups . '(\d+)' . $end,
                    '',
                    $action,
                ),
                $actions,
            ),
            $actions,
        );
    }

    private function _01Seed01Seed(OutputInterface $output)
    {
        $this->call($output, 'doctrine:schema:drop', array('--force' => true));
        $this->call($output, 'doctrine:schema:create');
        $this->call($output, 'doctrine:fixtures:load', array('--append' => true));
    }

    private function _01Import01Support(OutputInterface $output, Timer $timer)
    {
        $this->import('support', $output, $timer);
    }

    private function call(
        OutputInterface $output,
        string $cmdName,
        array $cmdArgs = null,
    ): int {
        $command = $this->getApplication()->find($cmdName);
        $input = new ArrayInput($cmdArgs ?? array());

        return $command->run($input, $output);
    }

    private function import(string $group, OutputInterface $output, Timer $timer): void
    {
        $imports = glob($this->projectDir . '/database/' . $group . '/*.sql');

        if (!$imports) {
            $output->writeln('<comment>No schema imported</>');

            return;
        }

        /** @var \PDO */
        $pdo = $this->db->getNativeConnection();

        if (!$pdo instanceof \PDO) {
            throw new \InvalidArgumentException('PDO connection is not found');
        }

        array_walk($imports, function(string $file) use ($pdo, $output, $timer, $group) {
            $timer->start();
            $output->write(
                sprintf(
                    'Importing <info>%s</> <comment>%s</>...',
                    $group,
                    Utils::truncate($file, 30, false),
                ),
            );

            $pdo->exec(file_get_contents($file));

            list($err, $code, $message) = $pdo->errorInfo();
            list($tag, $msg) = match ($err) {
                '00000' => array('info', 'done'),
                default => array('error', '[' . $code . '] ' . $message),
            };
            $elp = $timer->stop()->asString();

            $output->writeln(sprintf(' <%s>%s</> [%s]', $tag, $msg, $elp));
        });
    }

    public function __construct(
        private Connection $db,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        #[Autowire('%kernel.environment%')]
        private string $projectEnv,
        #[Autowire('%app.devpass%')]
        private string $devPass,
    ) {
        parent::__construct();
    }
}
