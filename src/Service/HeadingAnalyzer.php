<?php


namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HeadingAnalyzer
{
    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
    }

    public function checkHeadings(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();
            $h1Count = substr_count(strtolower($content), '<h1');

            return [
                'valid' => $h1Count === 1,
                'h1_count' => $h1Count,
                'message' => $h1Count === 1 ? 'Perfect H1 structure' :
                    ($h1Count === 0 ? 'No H1 found' : "Multiple H1s found ({$h1Count})")
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'h1_count' => 0,
                'message' => 'Could not analyze headings'
            ];
        }
    }
}
