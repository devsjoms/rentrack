<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseMailConfiguration extends Command
{
    protected $signature = 'rentrack:mail-diagnose';

    protected $description = 'Show a redacted summary of the active mail configuration.';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $settings = (array) config("mail.mailers.{$mailer}", []);
        $host = (string) ($settings['host'] ?? '');

        $this->table(['Setting', 'Status'], [
            ['Default mailer', $mailer !== '' ? $mailer : 'missing'],
            ['Transport', (string) ($settings['transport'] ?? 'not applicable')],
            ['Host', $host === 'smtp.gmail.com' ? 'smtp.gmail.com' : ($host === '' ? 'missing' : 'configured')],
            ['Port', (string) ($settings['port'] ?? 'not applicable')],
            ['Scheme', (string) ($settings['scheme'] ?? 'auto')],
            ['Username configured', blank($settings['username'] ?? null) ? 'no' : 'yes'],
            ['Password configured', blank($settings['password'] ?? null) ? 'no' : 'yes'],
            ['From address configured', blank(config('mail.from.address')) ? 'no' : 'yes'],
            ['Queue connection', (string) config('queue.default')],
            ['Configuration cache', file_exists(base_path('bootstrap/cache/config.php')) ? 'present' : 'absent'],
        ]);

        if ($mailer !== 'smtp') {
            $this->error('Mail is not configured for SMTP delivery. No message was sent.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
