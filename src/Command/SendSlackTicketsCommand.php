<?php

namespace App\Command;

use App\Service\NotionService;
use App\Service\SlackNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-slack-tickets-estimes',
    description: 'Send estimated tickets information to Slack',
)]
class SendSlackTicketsCommand extends Command
{
    public function __construct(
        private NotionService $notionService,
        private SlackNotificationService $slackNotificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $tickets = $this->notionService->getTicketsWithEstimation();

            if (empty($tickets)) {
                $io->success('No tickets with estimations found.');
                return Command::SUCCESS;
            }

            $messages = [];
            foreach ($tickets as $ticket) {
                $titleDisplay = $ticket['titleDisplay'];
                $title = $ticket['title'];
                $estimation = $ticket['estimation'];
                $timeSpent = $this->notionService->getTimeSpentForTicket($title);

                $messages[] = [
                    'titleDisplay' => $titleDisplay,
                    'timeSpent' => $timeSpent,
                    'estimation' => $estimation
                ];
            }

            // Envoi de la notification sur Slack avec gestion des couleurs
            $this->slackNotificationService->sendNotification($messages);

            $io->success('Notifications sent to Slack successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function formatTime(float $time): string
    {
        if (intval($time) == $time) {
            return sprintf("%dh", intval($time));
        }

        return sprintf("%.1fh", $time);
    }
}
