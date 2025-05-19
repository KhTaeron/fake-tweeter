<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ApiClientBaseService
{
    protected string $apiBaseUrl;
    protected ?string $jwtToken = null;

    public function __construct(protected HttpClientInterface $client, string $apiBaseUrl)
    {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/') . '/api';
    }

    public function setJwtToken(string $token): void
    {
        $this->jwtToken = $token;
    }

    protected function fetchJson(string $endpoint): ?array
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('GET', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            error_log('fetchJson error: ' . $e->getMessage());
            return null;
        }
    }

    protected function postJson(string $endpoint, array $data): bool
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('POST', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            if (in_array($statusCode, [200, 201])) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            error_log('postJson error: ' . $e->getMessage());
            return false;
        }
    }

    protected function postMultipartFile(string $endpoint, \SplFileInfo $file, string $fieldName = 'avatar'): bool
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->request('POST', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
                'multipart' => [
                    [
                        'name' => $fieldName,
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $file->getFilename(),
                    ]
                ]
            ]);

            return in_array($response->getStatusCode(), [200, 201]);
        } catch (\Throwable $e) {
            error_log('postMultipartFile error: ' . $e->getMessage());
            return false;
        }
    }

    protected function putJson(string $endpoint, array $data): bool
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('PUT', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            error_log('putJson error: ' . $e->getMessage());
            return false;
        }
        
    }

    protected function delete(string $endpoint): bool
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('DELETE', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
            ]);

            return in_array($response->getStatusCode(), [200, 204]);
        } catch (\Throwable $e) {
            error_log('delete error: ' . $e->getMessage());
            return false;
        }
    }

}
