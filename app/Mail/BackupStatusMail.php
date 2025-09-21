<?php

namespace App\Mail;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $backup;

    /**
     * Create a new message instance.
     */
    public function __construct(Backup $backup)
    {
        $this->backup = $backup;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "Backup Update for Project: {$this->backup->project->name}";

        return $this->subject($subject)
                    ->view('emails.backup_status');
    }
}