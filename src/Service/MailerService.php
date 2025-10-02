<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function sendMail(string $to, string $subject, string | TemplatedEmail  $content): void
    {

        if (is_string($content)) {
            $this->logger->debug("Sending email $subject");
            $email = (new Email())
                ->from(new Address('zetrashz@gmail.com', 'Comic Hood'))
                ->to($to)
                ->subject($subject)
                ->html($content);
            $this->mailer->send($email);
            $this->logger->debug("Email sent");
        }

        if ($content instanceof  TemplatedEmail) {
            $this->logger->critical("Templated Email sent not implemented yet");
        }
    }
}
