<?php

namespace App\Services;

use App\Mail\ProjectTaskCommentMail;
use App\Models\CompanySetting;
use App\Models\ProjectTaskComment;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class ProjectTaskCommentMailService
{
    public function notifyForComment(ProjectTaskComment $comment): void
    {
        $comment->loadMissing(['user', 'task.project.client.user', 'company']);

        $task = $comment->task;
        $project = $task?->project;
        $company = $comment->company;

        if (! $task || ! $project || ! $company) {
            return;
        }

        if ($comment->is_client) {
            $this->notifyStaff($comment, $task, $project, $company);
        } else {
            $this->notifyClient($comment, $task, $project, $company);
        }
    }

    private function notifyStaff(ProjectTaskComment $comment, $task, $project, $company): void
    {
        $actionUrl = flowdesk_tenant_url($company, route('projects.show', [$project, 'tab' => 'tasks'], false));

        $recipients = User::query()
            ->where('company_id', $company->id)
            ->workspaceStaff()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        foreach ($recipients as $recipient) {
            $this->send($comment, $task, $project, $company, $actionUrl, false, $recipient->email);
        }
    }

    private function notifyClient(ProjectTaskComment $comment, $task, $project, $company): void
    {
        $clientEmail = $project->client?->email
            ?: $project->client?->user?->email;

        if ($clientEmail === null || $clientEmail === '') {
            return;
        }

        $actionUrl = flowdesk_tenant_url($company, route('portal.projects.show', $project, false));

        $this->send($comment, $task, $project, $company, $actionUrl, true, $clientEmail);
    }

    private function send(
        ProjectTaskComment $comment,
        $task,
        $project,
        $company,
        string $actionUrl,
        bool $notifyClient,
        string $toEmail,
    ): void {
        $mailable = new ProjectTaskCommentMail($comment, $task, $project, $company, $actionUrl, $notifyClient);

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $smtp = $settings?->smtp;

        if (is_array($smtp) && ! empty($smtp['host'])) {
            Config::set('mail.mailers.flowdesk_tenant', [
                'transport' => 'smtp',
                'host' => $smtp['host'],
                'port' => (int) ($smtp['port'] ?? 587),
                'encryption' => $smtp['encryption'] ?? 'tls',
                'username' => $smtp['username'] ?? null,
                'password' => $smtp['password'] ?? null,
                'timeout' => null,
            ]);
            $fromAddress = $smtp['from_address'] ?? config('mail.from.address');
            $fromName = $smtp['from_name'] ?? config('mail.from.name');
            $mailable->from($fromAddress, $fromName);
            Mail::mailer('flowdesk_tenant')->to($toEmail)->send($mailable);

            return;
        }

        Mail::to($toEmail)->send($mailable);
    }
}
