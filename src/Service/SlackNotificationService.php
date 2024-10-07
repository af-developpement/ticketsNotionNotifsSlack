<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackNotificationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $slackWebhookUrl
    ) {}

    public function sendNotification(array $messages): void
    {
        $blocks = [];

        foreach ($messages as $message) {
            $titleDisplay = $message['titleDisplay'];
            $timeSpent = $message['timeSpent'];
            $estimation = $message['estimation'];
            $percentage = ($timeSpent / $estimation) * 100;

            if ($timeSpent > $estimation) {
                $color = 'danger';
            } elseif ($percentage >= 80 && $percentage <= 100) {
                $color = 'warning';
            } else {
                $color = 'good';
            }

            $blocks[] = [
                'color' => $color,
                'fields' => [
                    [
                        'title' => $titleDisplay,
                        'value' => sprintf("%s / %s \t | \t (%.1f%%)", $this->formatTime($timeSpent), $this->formatTime($estimation), $percentage),
                        'short' => false,
                    ],
                ],
            ];
        }

        $blocks[] = [
            'color' => '#cccccc',
            'fields' => [
                [
                    'title' => '',
                    'value' => "----------------------------------------------------------------------------------",
                    'short' => false,
                ],
            ],
        ];

        $this->httpClient->request('POST', $this->slackWebhookUrl, [
            'json' => [
                'attachments' => $blocks,
            ],
        ]);
    }

    private function formatTime(float $time): string
    {
        if (intval($time) == $time) {
            return sprintf("%dh", intval($time));
        }

        return sprintf("%.1fh", $time);
    }
}
