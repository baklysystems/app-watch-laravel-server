<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;
    public array $recipients;
    public $tries = 3;

    public function __construct(Project $project, array $recipients)
    {
        $this->project = $project;
        $this->recipients = $recipients;
    }

    public function handle(ReportService $reportService): void
    {
        try {
            $pdfContent = $reportService->generateWeekly($this->project);
            $startDate = now()->subWeek()->startOfWeek()->format('M j');
            $endDate = now()->subWeek()->endOfWeek()->format('M j, Y');

            $subject = "[Appswatch] Weekly Report — {$this->project->name} ({$startDate} - {$endDate})";

            Mail::html($reportService->generateHtml($this->project), function ($message) use ($pdfContent, $subject) {
                $message->to($this->recipients)
                    ->subject($subject)
                    ->attachData($pdfContent, "appswatch-weekly-{$this->project->slug}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            Log::info("Report: Sent weekly email for {$this->project->name} to " . implode(', ', $this->recipients));
        } catch (\Throwable $e) {
            Log::warning("Report: Failed to send for {$this->project->name} — {$e->getMessage()}");

            if ($this->attempts() < $this->tries) {
                $this->release(300); // Retry after 5 minutes
            }

            throw $e;
        }
    }
}