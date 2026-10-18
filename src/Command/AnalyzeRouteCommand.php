<?php

namespace App\Command;

use App\Service\RouteChecker;
use App\Service\HeadingAnalyzer;
use App\Service\ImageAltChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'route:analyze',
    description: 'Check if a URL or route is accessible and analyze headings'
)]
class AnalyzeRouteCommand extends Command
{
    public function __construct(
        private RouteChecker $routeChecker,
        private HeadingAnalyzer $headingAnalyzer,
        private ImageAltChecker $imageAltChecker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'URL to check (e.g., https://example.com or /about)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');

        // Normalize URL
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . ltrim($url, '/');
        }

        $io->title('🔧 Route Analyzer');
        $io->text("Analyzing: <info>$url</info>");
        $io->newLine();

        $passed = 0;
        $failed = 0;

        try {
            // Check 1: Status Code
            $statusResult = $this->routeChecker->checkStatus($url);

            if ($statusResult['accessible']) {
                $io->text("✅ Route is accessible");
                $io->text("   Status Code: <info>{$statusResult['status_code']}</info>");
                $io->text("   Response Time: <comment>{$statusResult['response_time']}ms</comment>");
                $passed++;
            } else {
                $io->text("❌ Route is not accessible");
                $io->text("   Status Code: <e>{$statusResult['status_code']}</e>");
                $io->text("   Response Time: <comment>{$statusResult['response_time']}ms</comment>");
                $failed++;
            }

            // Check 2: H1 Headings (only if status is 200)
            if ($statusResult['accessible']) {
                $headingResult = $this->headingAnalyzer->checkHeadings($url);

                if ($headingResult['valid']) {
                    $io->text("✅ Heading structure is valid");
                    $io->text("   <comment>{$headingResult['message']}</comment>");
                    $passed++;
                } else {
                    $io->text("❌ Heading structure needs fixing");
                    $io->text("   <comment>{$headingResult['message']}</comment>");
                    $failed++;
                }
            }

            // Check 3: Image Alt Tags
            if ($statusResult['accessible']) {
                $imageResult = $this->imageAltChecker->checkImageAlts($url);
                if ($imageResult['valid']) {
                    $io->text("✅ All images have alt tags");
                    $io->text("   <comment>{$imageResult['message']}</comment>");
                    $passed++;
                } else {
                    $io->text("❌ Images missing alt tags");
                    $io->text("   <comment>{$imageResult['message']}</comment>");
                    $failed++;
                }
            }

            $io->newLine();
            $io->text("✅ <info>Passed: $passed</info> ❌ <e>Failed: $failed</e>");
            $io->text("Completed " . ($passed + $failed) . " checks.");

            return $failed > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Analysis failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
