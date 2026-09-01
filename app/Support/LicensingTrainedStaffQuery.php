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

    /**
     * @return list<string>
     */
    public static function gatedPositionKeys(): array
    {
        return array_values(array_intersect(
            array_keys(config('position_exam_rules.positions', [])),
            self::eligiblePositionKeys(),
        ));
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
        $reservedRegistrationNumbers = LicenseNomination::reservedQuery()
            ->pluck('registration_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query = TrainingApplication::query()
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
            ->whereNotNull('assigned_position')
            ->when($reservedRegistrationNumbers !== [], function ($q) use ($reservedRegistrationNumbers) {
                $q->whereNotIn('registration_number', $reservedRegistrationNumbers);
            });

        return self::applyAssignedPositionEligibility($query);
    }

    /**
     * Restrict listing to earned assigned positions; manager and QA also require score + education gates.
     */
    public static function applyAssignedPositionEligibility(Builder $query): Builder
    {
        $eligible = self::eligiblePositionKeys();
        $gated = config('position_exam_rules.positions', []);
        $nonGated = array_values(array_diff($eligible, array_keys($gated)));

        return $query->where(function ($q) use ($gated, $nonGated, $eligible) {
            if ($nonGated !== []) {
                $q->whereIn('assigned_position', $nonGated);
            }

            foreach ($gated as $positionKey => $rules) {
                if (! in_array($positionKey, $eligible, true)) {
                    continue;
                }

                $minScore = (float) ($rules['min_score'] ?? 0);
                $requiredEducation = PositionExamAssigner::normalizeRequiredEducation($rules);

                $q->orWhere(function ($inner) use ($positionKey, $minScore, $requiredEducation) {
                    $inner->where('assigned_position', $positionKey)
                        ->where('exam_score', '>=', $minScore);

                    if ($requiredEducation !== []) {
                        $inner->where(function ($edu) use ($requiredEducation) {
                            foreach ($requiredEducation as $requirement) {
                                $edu->orWhereHas('user.educationBackgrounds', function ($eb) use ($requirement) {
                                    $eb->where('level', $requirement['level']);
                                    if ($requirement['program'] !== null) {
                                        $eb->where('program', $requirement['program']);
                                    }
                                });
                            }
                        });
                    }
                });
            }
        });
    }

    public static function applyFilters(Builder $query, Request $request): Builder
    {
        $positions = self::parsePositionFilter($request->query('position'));
        if ($positions === []) {
            // Invalid position filter → empty result
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('assigned_position', $positions);

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
        $finalPosition = $application->assigned_position;

        return [
            'registration_number' => $application->registration_number,
            'full_name' => trim(collect([
                $application->first_name,
                $application->middle_name,
                $application->last_name,
            ])->filter()->implode(' ')),
            'email' => $application->email ?: $application->user?->email,
            'final_position' => $finalPosition,
            'final_position_label' => TrainingApplication::positionLabel($finalPosition),
            'course_id' => $application->course_id,
            'session_year' => $application->course?->session_year,
        ];
    }
}
