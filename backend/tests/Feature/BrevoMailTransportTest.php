<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class BrevoMailTransportTest extends TestCase
{
    public function test_brevo_mailer_uses_the_smtp_transport(): void
    {
        config()->set('mail.mailers.brevo.username', 'test@example.com');
        config()->set('mail.mailers.brevo.password', 'test-smtp-key');

        $transport = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertStringContainsString('smtp-relay.brevo.com:587', (string) $transport);
    }
}
