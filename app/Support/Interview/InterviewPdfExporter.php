<?php

namespace App\Support\Interview;

use App\Models\InterviewSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InterviewPdfExporter
{
    public static function export(InterviewSession $session): Response
    {
        $session->load([
            'company',
            'questionSet',
            'panelists.user',
            'result.reviewer',
            'result.approver',
        ]);

        $snapshot = $session->result?->calculation_snapshot ?? [];
        $filename = 'interview-'.$session->session_code.'.pdf';

        return Pdf::loadView('exports.interview-consolidated-pdf', [
            'session' => $session,
            'questions' => $snapshot['questions'] ?? [],
            'varianceFlags' => $snapshot['variance_flags'] ?? [],
            'generatedAt' => now(),
        ])->download($filename);
    }
}
