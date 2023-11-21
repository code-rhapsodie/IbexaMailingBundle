<?php

/**
 * NovaeZMailingBundle Bundle.
 *
 * @package   Novactive\Bundle\eZMailingBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZMailingBundle/blob/master/LICENSE MIT Licence
 */

declare(strict_types=1);

namespace Novactive\Bundle\eZMailingBundle\Core\Mailer;

use Doctrine\ORM\EntityManagerInterface;
use Novactive\Bundle\eZMailingBundle\Core\Provider\Broadcast;
use Novactive\Bundle\eZMailingBundle\Core\Provider\MailingContent;
use Novactive\Bundle\eZMailingBundle\Entity\Mailing as MailingEntity;
use Novactive\Bundle\eZMailingBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;

/**
 * Class Mailing.
 */
class Mailing
{
    public function __construct(
        private readonly Simple                 $simpleMailer,
        private readonly MailingContent         $contentProvider,
        private readonly LoggerInterface        $logger,
        private readonly Broadcast              $broadcastProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface        $mailer,
        private readonly string                 $mailing,
        private readonly MailingProcess $mailingProcess
    )
    {
    }

    public function sendMailing(MailingEntity $mailing, string $forceRecipient = null): void
    {
        $nativeHtml = $this->contentProvider->preFetchContent($mailing);
        $broadcast = $this->broadcastProvider->start($mailing, $nativeHtml);

        $this->simpleMailer->sendStartSendingMailingMessage($mailing);
        if ($forceRecipient) {
            $fakeUser = new User();
            $fakeUser->setEmail($forceRecipient);
            $fakeUser->setFirstName('XXXX');
            $fakeUser->setLastName('YYYY');
            $contentMessage = $this->contentProvider->getContentMailing($mailing, $fakeUser, $broadcast);
            $this->logger->debug("Mailing Mailer starts to test {$contentMessage->getSubject()}.");
            $this->sendMessage($contentMessage);
        } else {
            $campaign = $mailing->getCampaign();
            $this->logger->notice("Mailing Mailer starts to send Mailing {$mailing->getName()}");
            $recipientCounts = 0;
            $userRepo = $this->entityManager->getRepository(User::class);
            $recipients = $userRepo->findValidRecipients($campaign->getMailingLists());

            $this->mailingProcess->runParallelProcess($broadcast->getId(), $this->fetchIterationFromUserList($recipients, 10));

            //send copy of email
            $fakeUser = new User();
            $fakeUser->setEmail($mailing->getCampaign()->getReportEmail());
            $fakeUser->setFirstName('XXXX');
            $fakeUser->setLastName('YYYY');
            $contentMessage = $this->contentProvider->getContentMailing($mailing, $fakeUser, $broadcast);
            $this->sendMessage($contentMessage);

            $this->broadcastProvider->store($broadcast);
            $this->logger->notice("Mailing {$mailing->getName()} induced {$recipientCounts} emails sent.");
        }
        $this->simpleMailer->sendStopSendingMailingMessage($mailing);
        $this->broadcastProvider->end($broadcast);
    }

    public function sendMessage(Message $message): void
    {
        $message->getHeaders()->addTextHeader('X-Transport', $this->mailing);
        $this->mailer->send($message);
    }

    /**
     * @param array<User> $users
     */
    private function fetchIterationFromUserList(array $users, int $iterationCount): \Generator
    {
        do {
            $usersId = [];

            foreach ($users as $user) {
                $usersId[] = $user->getId();
                if (\count($usersId) === $iterationCount) {
                    $data = $usersId;
                    $usersId = [];
                    yield $data;
                }
            }

            if (!empty($usersId)) {
                yield $usersId;
            }
        } while (!empty($users));
    }
}
