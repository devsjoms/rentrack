<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMailSmokeTest extends Command
{
    protected $signature = 'rentrack:mail-smoke {--to= : Explicit approved recipient} {--confirm : Send exactly one test email}';

    protected $description = 'Send one opt-in SMTP smoke-test email to an approved recipient.';

    public function handle(): int
    {
        $recipient = (string) $this->option('to');

        if (!$this->option('confirm') || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Refusing to send. Supply a valid --to address and the --confirm flag.');

            return self::FAILURE;
        }

        if (config('mail.default') !== 'smtp') {
            $this->error('Refusing to send because the active mailer is not SMTP.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is a single RentTrack V2 SMTP delivery smoke test. No action is required.',
                fn ($message) => $message->to($recipient)->subject('RentTrack V2 SMTP smoke test')
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error('SMTP did not accept the message. Review the Laravel log locally; credentials and transport details are intentionally not displayed.');

            return self::FAILURE;
        }

        $this->info('SMTP accepted one message. Inbox receipt must be confirmed separately by the approved recipient.');

        return self::SUCCESS;
    }
}
