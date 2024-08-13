<?php

namespace Svea\SveaPayment\Command\Console;

use Exception;
use Magento\Framework\Console\Cli;
use Svea\SveaPayment\Model\ResourceModel\Migrate\MigrateConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateConfigCommand extends Command
{
    const COMMAND_NAME = 'svea:migrate:config';

    /**
     * @var MigrateConfigInterface
     */
    private MigrateConfigInterface $migrateConfig;

    /**
     * @param MigrateConfigInterface $migrateConfig
     * @param string|null $name
     */
    public function __construct(
        MigrateConfigInterface $migrateConfig,
        string                 $name = null
    ) {
        $this->migrateConfig = $migrateConfig;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = Cli::RETURN_SUCCESS;
        $helper = $this->getHelper('question');
        $question = $this->createQuestion();
        if ($helper->ask($input, $output, $question) || !$input->isInteractive()) {
            try {
                $this->migrateConfig->execute($output);
                $output->writeln('Configs migrated successfully.');
            } catch (Exception $e) {
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    $e->getMessage()
                ));
                $exitCode = Cli::RETURN_FAILURE;
            }
        }

        return $exitCode;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Migrate SveaPayment and Maksuturva modules configs.');
        parent::configure();
    }

    /**
     * @return ConfirmationQuestion
     */
    private function createQuestion(): ConfirmationQuestion
    {
        return new ConfirmationQuestion('Are you sure you want to migrate Svea_Maksuturva module configs? Doing this will clear current Svea module configuration![y/N]', false);
    }
}
