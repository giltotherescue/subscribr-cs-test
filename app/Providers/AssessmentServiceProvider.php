<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AssessmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->validateAssessmentConfig();
        }
    }

    private function validateAssessmentConfig(): void
    {
        $sections = config('assessment.sections', []);
        $keys = [];

        foreach ($sections as $section) {
            foreach ($section['questions'] ?? [] as $question) {
                $key = $question['key'] ?? null;
                if ($key === null) {
                    throw new RuntimeException('Assessment question missing key');
                }
                if (in_array($key, $keys, true)) {
                    throw new RuntimeException("Duplicate assessment question key: {$key}");
                }
                $keys[] = $key;
            }
        }
    }
}
