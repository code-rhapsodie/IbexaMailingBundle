<?php



declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Core\Processor;

use Carbon\Carbon;
use CodeRhapsodie\IbexaMailingBundle\Core\Mailer\Mailing as MailingMailer;
use CodeRhapsodie\IbexaMailingBundle\Core\Utils\Clock;
use CodeRhapsodie\IbexaMailingBundle\Entity\Mailing;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;

class SendMailing extends Processor implements SendMailingProcessorInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MailingMailer
     */
    private $mailingMailer;

    /**
     * @var Registry
     */
    private $workflows;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailingMailer $mailingMailer,
        Registry $workflows
    ) {
        $this->entityManager = $entityManager;
        $this->mailingMailer = $mailingMailer;
        $this->workflows = $workflows;
    }

    public function execute(?DateTime $overrideDatetime = null): void
    {
        $mailingRepository = $this->entityManager->getRepository(Mailing::class);
        $pendingMailings = $mailingRepository->findByStatus(Mailing::PENDING);
        $clockDate = $overrideDatetime ?? Carbon::now();
        $clock = new Clock($clockDate);
        $matched = $sent = 0;
        foreach ($pendingMailings as $mailing) {
            /** @var Mailing $mailing */
            if ($clock->match($mailing)) {
                ++$matched;
                $this->logger->notice("{$mailing->getName()} has been matched pending and ready to be send.");
                if (
                    null !== $mailing->getLastSent() &&
                    $mailing->getLastSent()->format('Y-m-d-H') === $clockDate->format('Y-m-d-H')
                ) {
                    //Security here, if is has been sent during this current hour already, do nothing
                    $this->logger->debug(
                        "{$mailing->getName()} has been matched and IGNORED. It has been sent during this hour already."
                    );
                    continue;
                }

                $workflow = $this->workflows->get($mailing);
                if ($workflow->can($mailing, 'process')) {
                    $workflow->apply($mailing, 'process');
                    $this->entityManager->flush();
                    $this->mailingMailer->sendMailing($mailing);
                    $workflow->apply($mailing, 'finish');
                    if ($mailing->isRecurring()) {
                        $workflow->apply($mailing, 'reloop');
                    }
                    $this->entityManager->flush();
                }
                ++$sent;
            }
        }
        $this->logger->notice("{$matched} matched mailings induced {$sent} sendings.");
    }
}
