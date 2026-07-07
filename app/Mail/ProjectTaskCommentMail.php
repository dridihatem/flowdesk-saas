<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectTaskCommentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectTaskComment $comment,
        public ProjectTask $task,
        public Project $project,
        public Company $company,
        public string $actionUrl,
        public bool $notifyClient,
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        $subject = $this->notifyClient
            ? __('New reply on task :task — :project', [
                'task' => $this->task->title,
                'project' => $this->project->title,
            ])
            : __('New client comment on task :task — :project', [
                'task' => $this->task->title,
                'project' => $this->project->title,
            ]);

        return $this->subject($subject)->view('emails.project-task-comment');
    }
}
