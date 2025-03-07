<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Core\Mailer;

use CodeRhapsodie\IbexaMailingBundle\Core\DataHandler\Registration;
use CodeRhapsodie\IbexaMailingBundle\Core\DataHandler\Unregistration;
use CodeRhapsodie\IbexaMailingBundle\Core\Provider\MessageContent;
use CodeRhapsodie\IbexaMailingBundle\Entity\ConfirmationToken;
use CodeRhapsodie\IbexaMailingBundle\Entity\Mailing as MailingEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;

class Simple
{
    public function __construct(
        private readonly MessageContent $messageProvider,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly string $simpleMailer
    ) {
    }

    public function sendStartSendingMailingMessage(MailingEntity $mailing): void
    {
        $message = $this->messageProvider->getStartSendingMailing($mailing);
        $this->sendMessage($message);
    }

    public function sendStopSendingMailingMessage(MailingEntity $mailing): void
    {
        $message = $this->messageProvider->getStopSendingMailing($mailing);
        $this->sendMessage($message);
    }

    public function sendRegistrationConfirmation(Registration $registration, ConfirmationToken $token): void
    {
        $message = $this->messageProvider->getRegistrationConfirmation($registration, $token);
        $this->sendMessage($message);
    }

    public function sendUnregistrationConfirmation(Unregistration $unregistration, ConfirmationToken $token): void
    {
        $message = $this->messageProvider->getUnregistrationConfirmation($unregistration, $token);
        $this->sendMessage($message);
    }

    private function sendMessage(Message $message): void
    {
        $this->logger->debug("Simple Mailer sends {$message->getHeaders()->get('subject')->getBody()}.");
        $message->getHeaders()->addTextHeader('X-Transport', $this->simpleMailer);

        $this->mailer->send($message);
    }
}
