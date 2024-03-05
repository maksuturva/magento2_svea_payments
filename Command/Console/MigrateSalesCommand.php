<?php

namespace Svea\SveaPayment\Command\Console;

use Exception;
use Svea\SveaPayment\Model\ResourceModel\Migrate\MigrateSalesInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function strtotime;

class MigrateSalesCommand extends Command
{
    const COMMAND_NAME = 'svea:migrate:sales';
    const OPTION_DATE = 'date';

    /**
     * @var MigrateSalesInterface
     */
    private MigrateSalesInterface $migrateSales;

    /**
     * @param MigrateSalesInterface $migrateSales
     * @param string|null $name
     */
    public function __construct(
        MigrateSalesInterface $migrateSales,
        string                $name = null
    ) {
        $this->migrateSales = $migrateSales;
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
        $exitCode = 0;
        $fromDate = $this->getFromDate($input, $output);
        $helper = $this->getHelper('question');
        $question = $this->createQuestion();
        if (!$fromDate && !$helper->ask($input, $output, $question) || !$input->isInteractive()) {
            $output->writeln('Migration canceled.');
            return $exitCode;
        }
        try {
            $this->migrateSales->execute($fromDate);
            $output->writeln('Orders migrated successfully.');
        } catch (Exception $e) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $e->getMessage()
            ));
            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    private function getFromDate(InputInterface $input, OutputInterface $output): ?int
    {
        $fromDate = $input->getOption(self::OPTION_DATE);
        if ($fromDate) {
            $output->writeln('<info>Provided date is `' . $fromDate . '`</info>');
            $fromDate = strtotime($fromDate);
        }

        return $fromDate;
    }

    /**
     * @return ConfirmationQuestion
     */
    private function createQuestion(): ConfirmationQuestion
    {
        return new ConfirmationQuestion('Are you sure you want to migrate all orders? With --date option, you can migrate orders starting from a specific date. Format YYYY-MM-DD.[y/N]', false);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Migrate SveaPayment and Maksuturva modules sales. With --date option, ');
        $this->addOption(
            self::OPTION_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'Migrate orders from date. Format YYYY-MM-DD.'
        );
        parent::configure();
    }
}
