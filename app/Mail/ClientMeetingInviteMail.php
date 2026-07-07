<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Company;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientMeetingInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public Company $company,
        public WorkspaceCalendarEvent $event,
        public ?string $meetingUrl,
        public string $staffName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('client_meeting_invite_subject', [
                'company' => $this->company->name,
                'title' => $this->event->title,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client-meeting-invite',
        );
    }
}
