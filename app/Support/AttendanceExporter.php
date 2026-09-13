<?php

namespace App\Support;

use App\Models\AttendanceSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExporter
{
    public static function scanUrl(AttendanceSession $session): string
    {
        return url('/attendance/scan?token='.$session->qr_token);
    }

    public static function exportQrPdf(AttendanceSession $session): Response
    {
        $session->loadMissing('course');
        $scanUrl = self::scanUrl($session);

        return Pdf::loadView('exports.attendance-qr-pdf', [
            'session' => $session,
            'scanUrl' => $scanUrl,
            'qrDataUri' => QrCodeGenerator::pngDataUri($scanUrl, 300),
        ])->download(self::filename($session, 'qr-code', 'pdf'));
    }

    public static function exportCsv(AttendanceSession $session): StreamedResponse
    {
        $session->loadMissing('course');
        $records = self::records($session);
        $filename = self::filename($session, 'attendance', 'csv');

        return response()->streamDownload(function () use ($session, $records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Session'),
                __('Course'),
                __('Date'),
            ]);
            fputcsv($handle, [
                $session->name,
                $session->course?->name,
                $session->session_date?->format('Y-m-d'),
            ]);
            fputcsv($handle, []);

            fputcsv($handle, [
                __('Registration'),
                __('First name'),
                __('Last name'),
                __('Email'),
                __('Phone'),
                __('Company'),
                __('Scanned at'),
            ]);

            foreach ($records as $record) {
                $application = $record->trainingApplication;

                fputcsv($handle, [
                    $application?->registration_number,
                    $application?->first_name,
                    $application?->last_name,
                    $application?->email,
                    $application?->phone,
                    $application?->company_name,
                    $record->scanned_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function exportPdf(AttendanceSession $session): Response
    {
        $session->loadMissing('course');
        $records = self::records($session);

        return Pdf::loadView('exports.attendance-records-pdf', [
            'session' => $session,
            'records' => $records,
            'generatedAt' => now(),
        ])->download(self::filename($session, 'attendance', 'pdf'));
    }

    private static function records(AttendanceSession $session): Collection
    {
        return $session->attendanceRecords()
            ->with('trainingApplication')
            ->orderBy('scanned_at')
            ->get();
    }

    private static function filename(AttendanceSession $session, string $prefix, string $extension): string
    {
        $parts = [
            $prefix,
            Str::slug($session->course?->name ?? 'course'),
            Str::slug($session->name),
            $session->session_date?->format('Ymd') ?? now()->format('Ymd'),
        ];

        return implode('-', array_filter($parts)).'.'.$extension;
    }
}
