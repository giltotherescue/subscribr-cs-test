<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportSubmissionsController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $filename = 'assessment-submissions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Build headers
            $headers = [
                'attempt_id',
                'candidate_name',
                'candidate_email',
                'status',
                'started_at',
                'completed_at',
                'duration_seconds',
                'reviewed_at',
            ];

            // Add question columns
            $questionKeys = [];
            foreach (config('assessment.sections') as $section) {
                foreach ($section['questions'] as $question) {
                    $questionKeys[] = $question['key'];
                    $headers[] = 'answer__' . $question['key'];
                }
            }

            fputcsv($handle, $headers);

            // Stream attempts in chunks
            Attempt::with('answers')
                ->where('status', 'submitted')
                ->orderBy('completed_at', 'desc')
                ->chunk(100, function ($attempts) use ($handle, $questionKeys) {
                    foreach ($attempts as $attempt) {
                        $answersMap = $attempt->answers->pluck('answer_value', 'question_key')->toArray();

                        $row = [
                            $attempt->id,
                            $attempt->candidate_name,
                            $attempt->candidate_email,
                            $attempt->status,
                            $attempt->started_at?->toIso8601String(),
                            $attempt->completed_at?->toIso8601String(),
                            $attempt->duration_seconds,
                            $attempt->reviewed_at?->toIso8601String(),
                        ];

                        foreach ($questionKeys as $key) {
                            $row[] = $answersMap[$key] ?? '';
                        }

                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
