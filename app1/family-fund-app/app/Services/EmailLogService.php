<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;

class EmailLogService
{
    protected string $basePath = 'emails';
    protected string $attachmentsPath = 'emails/attachments';

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
            'attachments' => $this->saveAndFormatAttachments($email),
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

    protected function saveAndFormatAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $content = $attachment->getBody();
            $hash = md5($content);
            $extension = $this->getExtensionFromContentType($attachment->getContentType(), $attachment->getFilename());
            $storedFilename = $hash . '.' . $extension;
            $storedPath = $this->attachmentsPath . '/' . $storedFilename;

            // Only store if not already exists (deduplication)
            if (!Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->put($storedPath, $content);
            }

            $attachments[] = [
                'filename' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'size' => strlen($content),
                'hash' => $hash,
                'stored_filename' => $storedFilename,
            ];
        }

        return $attachments;
    }

    protected function getExtensionFromContentType(string $contentType, string $filename): string
    {
        // Try to get extension from original filename first
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext) {
            return strtolower($ext);
        }

        // Fall back to content type mapping
        $map = [
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'text/csv' => 'csv',
            'application/zip' => 'zip',
            'application/json' => 'json',
            'application/xml' => 'xml',
        ];

        return $map[$contentType] ?? 'bin';
    }
}
