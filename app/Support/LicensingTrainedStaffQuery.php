<?php

namespace App\Support;

use App\Models\LicenseNomination;
use App\Models\TrainingApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LicensingTrainedStaffQuery
{
    /**
     * @return list<string>
     */
    public static function eligiblePositionKeys(): array
    {
        return array_keys(config('licensing.eligible_positions', []));
    }

    public static function normalizePosition(?string $position): ?string
    {
        if ($position === null || $position === '') {
            return null;
        }

        $key = strtolower(trim($position));
        $key = str_replace([' ', '-'], '_', $key);

        $aliases = config('licensing.position_aliases', []);
        if (isset($aliases[$key])) {
            $key = $aliases[$key];
        }

        $eligible = self::eligiblePositionKeys();

        return in_array($key, $eligible, true) ? $key : null;
    }

    /**
     * @return list<string>
     */
    public static function parsePositionFilter(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return self::eligiblePositionKeys();
        }

        $parts = preg_split('/[,\s]+/', $raw) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $key = self::normalizePosition($part);
            if ($key) {
                $normalized[] = $key;
            }
        }

        return $normalized !== [] ? array_values(array_unique($normalized)) : [];
    }

    public static function baseQuery(): Builder
    {
        $eligible = self::eligiblePositionKeys();

        $reservedRegistrationNumbers = LicenseNomination::reservedQuery()
            ->pluck('registration_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return TrainingApplication::query()
            ->with(['course', 'user'])
            ->where('status', 'payment_completed')
            ->where('application_review_status', 'approved')
            ->where(function ($q) {
                $q->whereNotNull('account_verified_at')
                    ->orWhereNotNull('payment_verified_at');
            })
            ->whereNotNull('payment_verified_at')
            ->where('exam_passed', true)
            ->whereNotNull('exam_results_published_at')
            ->whereNotNull('registration_number')
            ->when($reservedRegistrationNumbers !== [], function ($q) use ($reservedRegistrationNumbers) {
                $q->whereNotIn('registration_number', $reservedRegistrationNumbers);
            })
            ->where(function ($q) use ($eligible) {
                $q->whereIn('assigned_position', $eligible)
                    ->orWhere(function ($inner) use ($eligible) {
                        $inner->whereNull('assigned_position')
                            ->whereIn('position', $eligible);
                    });
            });
    }

    public static function applyFilters(Builder $query, Request $request): Builder
    {
        $positions = self::parsePositionFilter($request->query('position'));
        if ($positions === []) {
            // Invalid position filter → empty result
            return $query->whereRaw('1 = 0');
        }

        $query->where(function ($q) use ($positions) {
            $q->whereIn('assigned_position', $positions)
                ->orWhere(function ($inner) use ($positions) {
                    $inner->whereNull('assigned_position')
                        ->whereIn('position', $positions);
                });
        });

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('session_year')) {
            $query->whereHas('course', fn ($q) => $q->where('session_year', $request->integer('session_year')));
        }

        if ($request->filled('registration_number')) {
            $query->where('registration_number', $request->string('registration_number')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'ilike', $term)
                    ->orWhere('middle_name', 'ilike', $term)
                    ->orWhere('last_name', 'ilike', $term)
                    ->orWhere('registration_number', 'ilike', $term)
                    ->orWhereRaw("concat_ws(' ', first_name, middle_name, last_name) ilike ?", [$term]);
            });
        }

        return $query->orderBy('registration_number');
    }

    public static function toApiRow(TrainingApplication $application): array
    {
        $finalPosition = $application->effectivePosition();

        return [
            'registration_number' => $application->registration_number,
            'full_name' => trim(collect([
                $application->first_name,
                $application->middle_name,
                $application->last_name,
            ])->filter()->implode(' ')),
            'final_position' => $finalPosition,
            'final_position_label' => $application->effectivePositionLabel(),
            'course_id' => $application->course_id,
            'session_year' => $application->course?->session_year,
        ];
    }
}
