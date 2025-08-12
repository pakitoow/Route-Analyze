<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RouteChecker
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function checkStatus(string $url): array
    {
        $startTime = microtime(true);

        try {

            $response = $this->httpClient->request('HEAD', $url, [
                'timeout' => 30,
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Symfony Route Analyzer/1.0'
                ],
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'accessible' => $statusCode === 200,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
            ];

        } catch (TransportExceptionInterface $e) {
            throw new \Exception("HTTP request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception("Request error: " . $e->getMessage());
        }
    }
}
