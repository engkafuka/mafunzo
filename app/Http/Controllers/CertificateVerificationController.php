<?php

namespace App\Http\Controllers;

use App\Models\TrainingApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(Request $request): View
    {
        $registrationNumber = trim((string) $request->query('reg', ''));

        if ($registrationNumber === '') {
            abort(404);
        }

        $application = TrainingApplication::query()
            ->with('course')
            ->where('registration_number', $registrationNumber)
            ->where('status', 'payment_completed')
            ->where('exam_passed', true)
            ->firstOrFail();

        $fullName = trim(collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
        ])->filter()->implode(' '));

        return view('certificates.verify', [
            'application' => $application,
            'fullName' => $fullName,
            'isIssued' => $application->certificate_issued_at !== null,
        ]);
    }
}
