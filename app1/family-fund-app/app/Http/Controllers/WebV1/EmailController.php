<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Models\OperationLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laracasts\Flash\Flash;

class EmailController extends AppBaseController
{
    /**
     * Email operations dashboard
     */
    public function index(Request $request)
    {
        if (!OperationsController::isAdmin()) {
            Flash::error('Access denied. Admin only.');
            return redirect('/');
        }

        // Email configuration
        $emailConfig = $this->getEmailConfig();

        // Email logs with search, filter, pagination
        $emailSearch = $request->get('search', '');
        $emailDateFrom = $request->get('date_from', '');
        $emailDateTo = $request->get('date_to', '');
        $page = (int) $request->get('page', 1);
        $emailPerPage = (int) $request->get('per_page', 20);
        $emailPerPage = in_array($emailPerPage, [20, 50, 100, 200]) ? $emailPerPage : 20;

        $emailLogsData = $this->getEmailLogs($emailSearch, $emailDateFrom, $emailDateTo, $page, $emailPerPage);
        $emailLogs = $emailLogsData['logs'];
        $emailLogsPaginator = $emailLogsData['paginator'];
        $emailLogsTotal = $emailLogsData['total'];

        return view('emails.index', compact(
            'emailConfig',
            'emailLogs',
            'emailLogsPaginator',
            'emailLogsTotal',
            'emailSearch',
            'emailDateFrom',
            'emailDateTo',
            'emailPerPage'
        ));
    }

    /**
     * View a single email log
     */
    public function show(Request $request, string $filename)
    {
        if (!OperationsController::isAdmin()) {
            Flash::error('Access denied. Admin only.');
            return redirect('/');
        }

        // Parse filename to get path: 20260111_123456_abc123.json -> emails/2026/01/filename
        if (preg_match('/^(\d{4})(\d{2})(\d{2})_/', $filename, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $path = "emails/{$year}/{$month}/{$filename}";

            if (Storage::disk('local')->exists($path)) {
                $content = Storage::disk('local')->get($path);
                $email = json_decode($content, true);

                if ($email) {
                    return view('emails.show', compact('email', 'filename'));
                }
            }
        }

        Flash::error('Email log not found.');
        return redirect(route('emails.index'));
    }

    /**
     * Send a test email
     */
    public function sendTest(Request $request)
    {
        if (!OperationsController::isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        $to = $request->input('email');

        try {
            Mail::raw(
                "This is a test email from Family Fund.\n\n" .
                "Environment: " . config('app.env') . "\n" .
                "Sent at: " . now()->format('Y-m-d H:i:s') . "\n" .
                "Mailer: " . config('mail.default') . "\n" .
                "Host: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port'),
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('Family Fund - Test Email');
                }
            );

            OperationLog::log(
                OperationLog::OP_SEND_TEST_EMAIL,
                OperationLog::RESULT_SUCCESS,
                "Test email sent to $to"
            );
            Flash::success("Test email sent to $to");
        } catch (\Exception $e) {
            OperationLog::log(
                OperationLog::OP_SEND_TEST_EMAIL,
                OperationLog::RESULT_ERROR,
                "Failed to send test email to $to: " . $e->getMessage()
            );
            Flash::error("Failed to send test email: " . $e->getMessage());
        }

        return redirect(route('emails.index'));
    }

    /**
     * Download an email attachment
     */
    public function downloadAttachment(Request $request, string $hash, string $filename)
    {
        if (!OperationsController::isAdmin()) {
            Flash::error('Access denied. Admin only.');
            return redirect('/');
        }

        // Sanitize hash - must be valid MD5 (32 hex chars)
        if (!preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            Flash::error('Invalid attachment reference.');
            return redirect(route('emails.index'));
        }

        // Find the attachment file by hash
        $attachmentsPath = 'emails/attachments';
        $files = Storage::disk('local')->files($attachmentsPath);

        $matchedFile = null;
        foreach ($files as $file) {
            if (str_starts_with(basename($file), $hash . '.')) {
                $matchedFile = $file;
                break;
            }
        }

        if (!$matchedFile || !Storage::disk('local')->exists($matchedFile)) {
            Flash::error('Attachment not found.');
            return redirect(route('emails.index'));
        }

        $content = Storage::disk('local')->get($matchedFile);
        $mimeType = Storage::disk('local')->mimeType($matchedFile);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get email configuration for display
     */
    private function getEmailConfig(): array
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption') ?: 'none',
            'username' => config('mail.mailers.smtp.username') ?: '(not set)',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'admin_address' => env('MAIL_ADMIN_ADDRESS', '(not set)'),
        ];
    }

    /**
     * Get email logs with search, date filter, and pagination
     */
    private function getEmailLogs(string $search, string $dateFrom, string $dateTo, int $page, int $perPage): array
    {
        $allLogs = [];
        $basePath = 'emails';

        try {
            // Get all year directories
            $years = Storage::disk('local')->directories($basePath);
            rsort($years); // Most recent first

            foreach ($years as $yearDir) {
                $months = Storage::disk('local')->directories($yearDir);
                rsort($months);

                foreach ($months as $monthDir) {
                    $files = Storage::disk('local')->files($monthDir);
                    rsort($files); // Most recent files first

                    foreach ($files as $file) {
                        $content = Storage::disk('local')->get($file);
                        $data = json_decode($content, true);

                        if (!$data) continue;

                        $logEntry = [
                            'file' => basename($file),
                            'timestamp' => $data['timestamp'] ?? null,
                            'subject' => $data['subject'] ?? '(no subject)',
                            'to' => $this->formatEmailAddresses($data['to'] ?? []),
                            'from' => $this->formatEmailAddresses($data['from'] ?? []),
                            'attachments' => count($data['attachments'] ?? []),
                        ];

                        // Apply date filter
                        if ($dateFrom && $logEntry['timestamp']) {
                            $logDate = Carbon::parse($logEntry['timestamp'])->format('Y-m-d');
                            if ($logDate < $dateFrom) continue;
                        }
                        if ($dateTo && $logEntry['timestamp']) {
                            $logDate = Carbon::parse($logEntry['timestamp'])->format('Y-m-d');
                            if ($logDate > $dateTo) continue;
                        }

                        // Apply search filter
                        if ($search) {
                            $searchLower = strtolower($search);
                            $matches = str_contains(strtolower($logEntry['subject']), $searchLower)
                                || str_contains(strtolower($logEntry['to']), $searchLower)
                                || str_contains(strtolower($logEntry['from']), $searchLower);
                            if (!$matches) continue;
                        }

                        $allLogs[] = $logEntry;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to read email logs: ' . $e->getMessage());
        }

        // Sort by timestamp descending
        usort($allLogs, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        // Paginate
        $total = count($allLogs);
        $logs = array_slice($allLogs, ($page - 1) * $perPage, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $logs,
            $total,
            $perPage,
            $page,
            [
                'path' => route('emails.index'),
                'query' => array_filter([
                    'search' => $search,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'per_page' => $perPage != 20 ? $perPage : null,
                ]),
            ]
        );

        return ['logs' => $logs, 'paginator' => $paginator, 'total' => $total];
    }

    /**
     * Format email addresses for display
     */
    private function formatEmailAddresses(array $addresses): string
    {
        return collect($addresses)
            ->map(fn($a) => $a['email'] ?? '')
            ->filter()
            ->implode(', ');
    }
}
