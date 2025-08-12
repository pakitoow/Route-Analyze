<?php


namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageAltChecker
{
    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
    }

    public function checkImageAlts(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();

            // Count total images
            $totalImages = substr_count(strtolower($content), '<img');

            // Count images with alt attributes
            $imagesWithAlt = preg_match_all('/<img[^>]+alt\s*=\s*["\'][^"\']*["\'][^>]*>/i', $content);

            $missingAlt = $totalImages - $imagesWithAlt;

            return [
                'valid' => $missingAlt === 0 && $totalImages > 0,
                'total_images' => $totalImages,
                'missing_alt' => $missingAlt,
                'message' => $totalImages === 0 ? 'No images found' :
                    ($missingAlt === 0 ? 'All images have alt tags' : "{$missingAlt} images missing alt tags")
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'total_images' => 0,
                'missing_alt' => 0,
                'message' => 'Could not analyze images'
            ];
        }
    }
}
