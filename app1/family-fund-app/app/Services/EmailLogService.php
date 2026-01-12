<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;

class EmailLogService
{
    protected string $basePath = 'emails';

    public function log(Email $email): string
    {
        $timestamp = now();
        $directory = $this->basePath . '/' . $timestamp->format('Y/m');
        $filename = $timestamp->format('Ymd_His') . '_' . uniqid() . '.json';
        $filepath = $directory . '/' . $filename;

        $logEntry = [
            'timestamp' => $timestamp->toIso8601String(),
            'from' => $this->formatAddresses($email->getFrom()),
            'to' => $this->formatAddresses($email->getTo()),
            'cc' => $this->formatAddresses($email->getCc()),
            'bcc' => $this->formatAddresses($email->getBcc()),
            'reply_to' => $this->formatAddresses($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'text_body' => $email->getTextBody(),
            'html_body' => $email->getHtmlBody(),
            'attachments' => $this->formatAttachments($email),
        ];

        Storage::disk('local')->put(
            $filepath,
            json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $filepath;
    }

    protected function formatAddresses(?array $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        return array_map(function ($address) {
            return [
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ];
        }, $addresses);
    }

    protected function formatAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'filename' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'size' => strlen($attachment->getBody()),
            ];
        }

        return $attachments;
    }
}
