<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridMailService
{
    public function sendSimpleBatch(
        string $apiKey,
        Collection $contacts,
        string $subject,
        string $html,
        string $fromEmail,
        string $fromName,
    ): void {
        $this->sendHtmlPerRecipient($apiKey, $subject, $this->staticHtmlForContacts($contacts, $html), $fromEmail, $fromName);
    }

    /**
     * @param  array<int, array{email: string, name: string, html: string, subject?: string}>  $recipients
     */
    public function sendHtmlPerRecipient(
        string $apiKey,
        string $defaultSubject,
        array $recipients,
        string $fromEmail,
        string $fromName,
    ): void {
        foreach ($recipients as $row) {
            $to = filter_var($row['email'] ?? '', FILTER_VALIDATE_EMAIL);
            if (! is_string($to) || $to === '') {
                continue;
            }
            $name = (string) ($row['name'] ?? $to);
            $html = (string) ($row['html'] ?? '');
            $oneSubject = trim((string) ($row['subject'] ?? $defaultSubject));

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post('https://api.sendgrid.com/v3/mail/send', [
                    'personalizations' => [
                        [
                            'to' => [
                                [
                                    'email' => $to,
                                    'name' => $name,
                                ],
                            ],
                            'subject' => $oneSubject,
                        ],
                    ],
                    'from' => [
                        'email' => $fromEmail,
                        'name' => $fromName,
                    ],
                    'subject' => $oneSubject,
                    'content' => [
                        [
                            'type' => 'text/html',
                            'value' => $html,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                $msg = (string) ($response->json('errors.0.message') ?? $response->body());
                Log::error('sendgrid.mail_failed', [
                    'email' => $to,
                    'status' => $response->status(),
                    'message' => $msg,
                ]);
                if ($response->clientError() || $response->serverError()) {
                    throw new \RuntimeException(
                        __('email_send_sendgrid_error', [
                            'email' => $to,
                            'message' => $msg,
                        ])
                    );
                }
            }
        }
    }

    private function staticHtmlForContacts(Collection $contacts, string $html): array
    {
        $out = [];
        foreach ($contacts as $contact) {
            $to = filter_var((string) ($contact->email ?? ''), FILTER_VALIDATE_EMAIL);
            if (! is_string($to) || $to === '') {
                continue;
            }
            $out[] = [
                'email' => $to,
                'name' => (string) ($contact->name ?? $to),
                'html' => $html,
            ];
        }

        return $out;
    }
}
