<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotionService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $notionApiKey,
        private string $databaseId,
        private string $timeTrackingDatabaseId
    ) {}

    public function getTicketsWithEstimation(): array
    {
        $response = $this->httpClient->request('POST', 'https://api.notion.com/v1/databases/' . $this->databaseId . '/query', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->notionApiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'filter' => [
                    'property' => 'Estimation (en h)',
                    'number' => [
                        'is_not_empty' => true,
                    ],
                ],
                'page_size' => 100,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Error querying Notion API: ' . $response->getContent(false));
        }

        $data = $response->toArray();
        $tickets = [];

        foreach ($data['results'] as $result) {
            $tickets[] = [
                'title' => $result['properties']['Name']['title'][0]['plain_text'] ?? 'N/A',
                'estimation' => (float)$result['properties']['Estimation (en h)']['number'] ?? 0,
            ];
        }

        return $tickets;
    }


    public function getTimeSpentForTicket(string $ticketTitle): int
    {
        $response = $this->httpClient->request('POST', 'https://api.notion.com/v1/databases/' . $this->timeTrackingDatabaseId . '/query', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->notionApiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'filter' => [
                    'property' => 'Qu\'ai-je fait',
                    'title' => [
                        'equals' => $ticketTitle,
                    ],
                ],
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Error querying Notion API: ' . $response->getContent(false));
        }

        $data = $response->toArray();
        $totalTime = 0;

        foreach ($data['results'] as $result) {
            $totalTime += $result['properties']['Combien de temps ?']['number'] ?? 0;
        }

        return $totalTime;
    }

}
