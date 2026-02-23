<?php

namespace App\Command;

use App\Entity\Announcement;
use App\Repository\PostRepository;
use App\Service\FeedIntelligenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:feed:daily-digest', description: 'Generate daily highlights and trend alerts for the feed')]
class FeedDailyDigestCommand extends Command
{
    public function __construct(
        private PostRepository $postRepository,
        private FeedIntelligenceService $feedIntelligenceService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-highlights', null, InputOption::VALUE_NONE, 'Do not publish daily highlights')
            ->addOption('no-trends', null, InputOption::VALUE_NONE, 'Do not publish trends alert');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $since = (new \DateTimeImmutable('now'))->modify('-1 day');
        $posts = $this->postRepository->findSinceWithMetrics($since, 250);

        if ($posts === []) {
            $io->warning('No recent posts found.');

            return Command::SUCCESS;
        }

        if (!$input->getOption('no-highlights')) {
            $highlights = $this->feedIntelligenceService->buildDailyHighlights($posts, 5);
            if ($highlights !== []) {
                $lines = [];
                foreach ($highlights as $index => $item) {
                    $lines[] = sprintf(
                        '%d. %s (likes: %d, comments: %d)',
                        $index + 1,
                        (string) ($item['title'] ?? 'Post'),
                        (int) ($item['likes'] ?? 0),
                        (int) ($item['comments'] ?? 0)
                    );
                }

                $announcement = (new Announcement())
                    ->setTitle('Top 5 posts du jour')
                    ->setTag('highlight')
                    ->setContent(implode("\n", $lines))
                    ->setMediaType('text')
                    ->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($announcement);
                $io->success('Daily highlights announcement prepared.');
            }
        }

        if (!$input->getOption('no-trends')) {
            $trends = $this->feedIntelligenceService->detectTrendingTopics($posts, 7);
            if ($trends !== []) {
                $lines = ["Sujets chauds detectes :"];
                foreach ($trends as $trend) {
                    $lines[] = sprintf('- %s (%d)', (string) $trend['topic'], (int) $trend['count']);
                }

                $announcement = (new Announcement())
                    ->setTitle('Alertes tendances')
                    ->setTag('trend')
                    ->setContent(implode("\n", $lines))
                    ->setMediaType('text')
                    ->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($announcement);
                $io->success('Trend alert announcement prepared.');
            }
        }

        $this->entityManager->flush();
        $io->success('Feed daily digest completed.');

        return Command::SUCCESS;
    }
}
