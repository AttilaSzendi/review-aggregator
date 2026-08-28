<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReviewImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-reviews',
    description: 'Fetch reviews from all configured providers and store them.',
)]
final class ImportReviewsCommand extends Command
{
    public function __construct(private readonly ReviewImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing reviews');

        $result = $this->importer->import();

        $io->success(sprintf(
            '%d review(s) processed: %d created, %d updated.',
            $result->total(),
            $result->created(),
            $result->updated(),
        ));

        return Command::SUCCESS;
    }
}
