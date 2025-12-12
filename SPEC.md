## 1) Overview + key decisions

### What we’re building

A small, one-time-use Laravel 12 + Livewire app that lets candidates start an assessment (name + email), answer a fixed set of questions, autosaves reliably to sqlite, tracks time (server-truth), and gives admins a minimal review + CSV export UI.

### Key decisions (simple, robust, not overkill)

1. **Assessment definition lives in a single repo file** (`config/assessment.php`)
   *No editor, no database-managed questions.* This is fastest and least bug-prone for a throwaway hiring round.

2. **Single-page assessment runner (all sections on one page)**
   Livewire multi-step flows often add state edge cases (back button, step routing, partial validation). One page is simpler and more reliable, especially with autosave + resume-by-token.

3. **Autosave via Livewire polling (every 15 seconds) + explicit save on submit**
   This avoids “request per keystroke” load. It’s robust even if the candidate never blurs a field. It also works well with intermittent network issues.

4. **Server-truth duration = `completed_at - started_at`**
   Simple and auditable. No attempt to measure “active typing time” (overkill for this use).

5. **Admin protected by HTTP Basic Auth using env credentials**
   Fast to implement, safe enough for internal use, avoids introducing full user auth.

6. **Answers stored in a separate table keyed by stable `question_key`**
   Meets your schema requirement and keeps exports straightforward. We’ll enforce uniqueness with a composite unique index.

---

## 2) Detailed requirements

### 2.1 Candidate flow

#### Landing / start page (`GET /`)

* Shows:

  * Brief instructions (copy provided in section 8)
  * Fields: **Full name**, **Email**
  * “Start assessment” button
* Validation:

  * name: required, string, max 200
  * email: required, email:rfc,dns, max 254

#### Start (`POST` via Livewire action)

On Start:

* Create `attempts` record:

  * `token`: unguessable (64 hex chars)
  * `candidate_name`, `candidate_email`
  * `status`: `in_progress`
  * `started_at`: now()
  * `assessment_version`: from config (e.g. `2025-12-11`)
* Redirect candidate to resume link:
  **`/a/{token}`**
* Show in UI:

  * “Keep this link to resume.”

#### Assessment runner (`GET /a/{token}`)

* Loads attempt by token (404-friendly error page if invalid).
* Renders all sections + questions in the order of your assessment definition.
* All questions are **free-text textareas** (minimal rendering rules).
* Autosave requirements:

  * Save answers periodically (every 15 seconds) and on submit
  * Refresh/back should not lose work
  * Show clear “Saving / Saved / Offline” status
* Optional “progress indicator”:

  * Minimal: “Answered X of Y required questions” (cheap to compute)

#### Submit

On Submit:

* Validate required answers (based on assessment definition `required: true`)
* Persist latest answers
* Set:

  * `completed_at`: now()
  * `status`: `submitted`
  * `duration_seconds`: `completed_at - started_at` (integer seconds)
* Redirect to completion page: **`/a/{token}/done`**

#### Completion page (`GET /a/{token}/done`)

* Shows “You’re done” confirmation copy.
* Does **not** allow editing.
* If candidate visits `/a/{token}` after submission, show read-only banner: “Already submitted” with link to `/done`.

### 2.2 Resume / reliability behaviors

* **Resume**: token link always rehydrates saved answers from DB.
* **Network interruptions**:

  * If autosave fails, show “Offline / Not saved” banner.
  * Next successful autosave clears the banner.
* **Inline validation errors**:

  * Only enforced on Submit (to avoid blocking writing).
* **Multiple tabs**:

  * Behavior: **last write wins**.
  * Show warning banner if another tab is active recently (simple session heartbeat approach described in section 6).

---

## 3) Database schema + migrations (explicit)

### 3.1 `attempts` table

**Fields**

* `id` BIGINT UNSIGNED auto-increment
* `token` CHAR(64) UNIQUE (public resume identifier; never expose `id` publicly)
* `assessment_version` VARCHAR(32) (snapshot of config version at start)
* `candidate_name` VARCHAR(200)
* `candidate_email` VARCHAR(254)
* `status` ENUM(`in_progress`, `submitted`) default `in_progress`
* `started_at` TIMESTAMP NULL
* `completed_at` TIMESTAMP NULL
* `duration_seconds` INT UNSIGNED NULL
* `last_activity_at` TIMESTAMP NULL (updated on autosave)
* **Admin-only fields (optional but useful):**

  * `reviewed_at` TIMESTAMP NULL
  * `admin_notes` TEXT NULL
* **Multi-tab detection fields (simple):**

  * `active_session_id` CHAR(36) NULL (UUID from browser/localStorage)
  * `active_session_updated_at` TIMESTAMP NULL
* `created_at`, `updated_at` TIMESTAMPs

**Indexes**

* UNIQUE: `token`
* INDEX: `candidate_email`
* INDEX: `candidate_name`
* INDEX: `status`
* INDEX: `started_at`
* INDEX: `completed_at`

### 3.2 `answers` table

**Fields**

* `id` BIGINT UNSIGNED auto-increment
* `attempt_id` BIGINT UNSIGNED (FK -> attempts.id, cascade delete)
* `question_key` VARCHAR(100) (stable identifier from config)
* `answer_value` LONGTEXT NULL
* `created_at`, `updated_at`

**Constraints / indexes**

* UNIQUE (`attempt_id`, `question_key`)  ← critical for upsert
* INDEX (`attempt_id`)
* (Optional) INDEX (`question_key`) if you expect frequent per-question analytics (not needed for throwaway)

### 3.3 Migrations (implementation-ready)

```php
// database/migrations/2025_12_11_000001_create_attempts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();

            $table->char('token', 64)->unique();
            $table->string('assessment_version', 32);

            $table->string('candidate_name', 200);
            $table->string('candidate_email', 254);

            $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->char('active_session_id', 36)->nullable();
            $table->timestamp('active_session_updated_at')->nullable();

            $table->timestamps();

            $table->index('candidate_email');
            $table->index('candidate_name');
            $table->index('status');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
```

```php
// database/migrations/2025_12_11_000002_create_answers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attempt_id')
                ->constrained('attempts')
                ->cascadeOnDelete();

            $table->string('question_key', 100);
            $table->longText('answer_value')->nullable();

            $table->timestamps();

            $table->unique(['attempt_id', 'question_key']);
            $table->index('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
```

**Soft deletes vs archive**: skip soft deletes. This is throwaway; simplest is hard delete + optional retention purge command.

---

## 4) Assessment definition file format + example skeleton

### 4.1 Where it lives

* `config/assessment.php`

### 4.2 How keys are generated/maintained (avoid collisions)

* **Keys are explicitly set** in the config (do not auto-generate at runtime).
* Convention:

  * Prefix with section number and question label: `s1_q1a_angry_billing`
  * Keep stable even if you tweak wording.
  * Never reuse a key for a different question.
* Add a small runtime validator (on boot) that asserts all keys are unique; fail fast in dev.

### 4.3 Minimal structure

* `version` string (store in attempts)
* `title`
* `sections[]`:

  * `key`, `title`, optional `description`
  * `questions[]`:

    * `key` (stable)
    * `title` (short)
    * `prompt` (full text; paste your provided assessment content here)
    * `required` boolean
    * `rows` optional (textarea height hint)

### 4.4 Example skeleton (not rewriting your full questions)

```php
// config/assessment.php
return [
    'version' => '2025-12-11',
    'title' => 'Customer Support Lead — Assessment (Subscribr)',

    'sections' => [
        [
            'key' => 'email_responses',
            'title' => '1. Email Responses',
            'description' => 'Write the email responses as if you work at Subscribr.',
            'questions' => [
                [
                    'key' => 's1_q1a_angry_billing',
                    'title' => '1A. Angry billing and account issue',
                    'prompt' => 'PASTE YOUR FULL 1A PROMPT HERE...',
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's1_q1b_loyal_blocked',
                    'title' => '1B. Loyal but blocked technical user',
                    'prompt' => 'PASTE YOUR FULL 1B PROMPT HERE...',
                    'required' => true,
                    'rows' => 10,
                ],
            ],
        ],

        // ...
        // Repeat for sections 2–8, mapping each sub-question to a unique key.
        // For section 6 (three replies), model as three separate questions:
        // s6_reply1, s6_reply2, s6_reply3
    ],
];
```

### 4.5 How Livewire maps answers

* Livewire component keeps an associative array:

  * `$answers = [ 's1_q1a_angry_billing' => '...', ... ]`
* Save to DB by `question_key`.

---

## 5) Routes + Livewire component map

### 5.1 Routes

```php
// routes/web.php
use App\Livewire\Candidate\StartAssessment;
use App\Livewire\Candidate\AssessmentRunner;
use App\Livewire\Candidate\AssessmentComplete;

use App\Livewire\Admin\AttemptsIndex;
use App\Livewire\Admin\AttemptShow;
use App\Http\Controllers\Admin\ExportSubmissionsController;

Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/', StartAssessment::class)->name('start');
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/a/{token}', AssessmentRunner::class)->name('attempt');
    Route::get('/a/{token}/done', AssessmentComplete::class)->name('attempt.done');
});

// Admin (HTTP Basic Auth + extra throttling)
Route::prefix('admin')
    ->middleware(['admin.basic', 'throttle:60,1'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.attempts'))->name('admin.home');
        Route::get('/attempts', AttemptsIndex::class)->name('admin.attempts');
        Route::get('/attempts/{attempt}', AttemptShow::class)->name('admin.attempts.show');

        Route::get('/export/submissions.csv', ExportSubmissionsController::class)
            ->name('admin.export.submissions');
    });
```

### 5.2 Middleware

* `admin.basic` → custom middleware reading env vars:

  * `ASSESSMENT_ADMIN_USER`
  * `ASSESSMENT_ADMIN_PASS`

### 5.3 Livewire components

**Candidate**

* `App\Livewire\Candidate\StartAssessment`

  * form, validation, create attempt, redirect
* `App\Livewire\Candidate\AssessmentRunner`

  * render sections/questions, autosave, submit
* `App\Livewire\Candidate\AssessmentComplete`

  * completion copy, show summary metadata (optional)

**Admin**

* `App\Livewire\Admin\AttemptsIndex`

  * search, sort, list
* `App\Livewire\Admin\AttemptShow`

  * display answers in order, edit notes, mark reviewed

**Controller**

* `App\Http\Controllers\Admin\ExportSubmissionsController`

  * streamed CSV export

---

## 6) Autosave + timing logic details

### 6.1 Server-truth timing

* `started_at` is set once, at attempt creation.
* UI timer uses `started_at` as base:

  * server computes `initial_elapsed = now()->diffInSeconds(started_at)`
  * JS increments client-side for display only
* On submit:

  * `completed_at = now()`
  * `duration_seconds = completed_at->diffInSeconds(started_at)`

### 6.2 Autosave mechanism (minimal + robust)

**Approach**

* Use `wire:model.defer` on all textareas (no request per keystroke)
* Use `wire:poll.15s="autosave"` in the runner component
* Also call `autosave()` inside `submit()` before final validation (ensures latest state persisted)

**Persistence strategy**

* Upsert answers by `(attempt_id, question_key)` uniqueness.

Example method (runner component):

```php
public function autosave(): void
{
    if ($this->attempt->status === 'submitted') return;

    $now = now();

    $rows = [];
    foreach ($this->answers as $key => $value) {
        $rows[] = [
            'attempt_id' => $this->attempt->id,
            'question_key' => $key,
            'answer_value' => is_string($value) ? $value : null,
            'updated_at' => $now,
            'created_at' => $now,
        ];
    }

    // Upsert all answers each poll (small N, simplest/robust)
    \App\Models\Answer::upsert(
        $rows,
        ['attempt_id', 'question_key'],
        ['answer_value', 'updated_at']
    );

    $this->attempt->forceFill([
        'last_activity_at' => $now,
        'active_session_id' => $this->sessionId,
        'active_session_updated_at' => $now,
    ])->save();

    $this->lastSavedAt = $now->toIso8601String();
    $this->dispatch('autosaved', at: $this->lastSavedAt);
}
```

### 6.3 “Saved” state + offline state

* In Blade, use Alpine to track:

  * `dirty` (true on any input event)
  * `lastSavedAt` (updated on `autosaved` event)
* Display:

  * If dirty: “Saving soon…”
  * If not dirty: “Saved {time}”
  * If autosave throws exception: show “We couldn’t save. Check your connection.”

### 6.4 Multiple tabs warning (simple)

* On mount, generate/restore `sessionId` in browser localStorage (UUID).
* Send `sessionId` to server on each autosave.
* Server keeps `active_session_id` + `active_session_updated_at`.
* If current request’s `sessionId` differs and `active_session_updated_at` is within last 60 seconds:

  * set a Livewire flag `showMultiTabWarning = true`
* Banner copy: “This assessment is open in another tab. Changes may overwrite each other.”

---

## 7) Security & privacy measures (practical)

1. **Long random tokens**

   * Token generation: `bin2hex(random_bytes(32))` → 64-char hex
   * Unique index on token

2. **Rate limiting**

   * `GET /` and start action: `throttle:10,1`
   * `GET /a/{token}`: `throttle:60,1`
   * Admin: also throttled

3. **Admin gate**

   * HTTP Basic Auth middleware with env credentials
   * No full user auth system

4. **XSS-safe rendering**

   * Candidate inputs stored as plain text
   * Admin display uses escaped rendering:

     * `nl2br(e($answer))` (or keep as `<pre>` with escaping)

5. **PII minimization**

   * Only name + email stored.

6. **Retention plan (recommendation)**

   * Add config `ASSESSMENT_RETENTION_DAYS=180`
   * Provide an artisan command `attempts:purge-old` that deletes attempts older than retention (and cascades answers).
   * Optional: schedule daily in `app/Console/Kernel.php` (or Laravel 12 scheduling equivalent).

---

## 8) UI/UX + copy (ready to paste)

### Landing / start page copy

**Title:** Subscribr — Customer Support Lead Assessment

**Body:**
Thanks for taking the time to complete this assessment.

* Expected time: **60 minutes**
* Please do this in **one sitting if possible**
* You may reference the Subscribr Help Center: [https://subscribr.ai/help](https://subscribr.ai/help)
* You may use AI tools (ChatGPT, Claude, etc.) and web search as you normally would
* Please do not paste AI output directly without editing — we want to see your thinking

Enter your name and email to begin. You’ll receive a private link you can use to resume later.

**Fields:**

* Full name
* Email address

**Button:** Start assessment

### Autosave indicator copy

* **Autosave is on.** Your work saves automatically every ~15 seconds.
* **Last saved:** {time}
* If you see a connection warning, keep this tab open and try again.

### Completion screen copy

**Title:** You’re done ✅
Thanks — your assessment has been submitted successfully. You may close this tab.

### Admin empty states

**Attempts list empty:**
No attempts yet.

**Export hint (top-right):**
Export submissions as CSV

---

## 9) Testing plan (lightweight)

### 9.1 Minimal automated feature tests

1. **Start creates attempt + token**

   * Assert attempt row exists
   * Assert `token` length 64
   * Assert `started_at` set and `status=in_progress`
   * Assert redirect to `/a/{token}`

2. **Submitting saves answers + timestamps**

   * Create attempt with `started_at` in the past
   * Fill answers for required questions
   * Call `submit()`
   * Assert:

     * `status=submitted`
     * `completed_at` set
     * `duration_seconds` computed
     * answers exist in `answers` table for the keys

3. **Admin gate blocks unauthorized**

   * Request `/admin/attempts` without basic auth → 401
   * With correct header → 200

4. **Export works**

   * Seed 1–2 submitted attempts with answers
   * GET `/admin/export/submissions.csv`
   * Assert 200, content-type includes `text/csv`
   * Assert CSV includes candidate email and at least one answer column header

### 9.2 Manual QA checklist

* Start assessment → redirected to token URL
* Type in multiple answers, wait 15s → refresh → answers persist
* Simulate offline (devtools) → see “not saved” warning → reconnect → saved clears
* Open same attempt in two tabs → see multi-tab warning
* Submit with missing required answers → inline errors show, submission blocked
* Submit successfully → completion page, runner becomes read-only
* Admin list search works (name/email)
* Admin detail shows answers in correct order
* CSV export opens correctly in Google Sheets / Excel (newlines preserved)

---

## 10) Implementation plan (phases + granular tasks)

### Phase 1 — Data model + assessment config

**Task 1: Create migrations**

* **Goal:** Store attempts + answers reliably
* **Files:**

  * `database/migrations/*create_attempts_table.php`
  * `database/migrations/*create_answers_table.php`
* **Acceptance criteria:**

  * `php artisan migrate` succeeds
  * Unique constraints exist (`token`, `attempt_id+question_key`)

**Task 2: Create models + relationships**

* **Goal:** Simple Eloquent access
* **Files:**

  * `app/Models/Attempt.php`
  * `app/Models/Answer.php`
* **Acceptance criteria:**

  * `$attempt->answers()` relationship works
  * Can upsert answers

**Task 3: Add assessment definition**

* **Goal:** Single source of truth for sections/questions
* **Files:**

  * `config/assessment.php`
* **Acceptance criteria:**

  * Config loads
  * Keys are unique (runtime assertion in dev)

---

### Phase 2 — Candidate experience (start → answer → submit)

**Task 4: Start page Livewire component**

* **Goal:** Collect name/email and create attempt + token
* **Files:**

  * `app/Livewire/Candidate/StartAssessment.php`
  * `resources/views/livewire/candidate/start-assessment.blade.php`
  * `routes/web.php`
* **Acceptance criteria:**

  * Valid form creates attempt and redirects to `/a/{token}`
  * Invalid input shows inline validation

**Task 5: Assessment runner component (render + resume)**

* **Goal:** Render all questions from config and load saved answers
* **Files:**

  * `app/Livewire/Candidate/AssessmentRunner.php`
  * `resources/views/livewire/candidate/assessment-runner.blade.php`
* **Acceptance criteria:**

  * Visiting `/a/{token}` shows all sections/questions
  * Existing answers populate correctly
  * Invalid token shows friendly 404 page

**Task 6: Autosave via polling**

* **Goal:** Persist answers every 15 seconds + show save state
* **Files:**

  * Runner component + blade (add `wire:poll.15s="autosave"`)
  * Minimal Alpine glue for “dirty/saved”
* **Acceptance criteria:**

  * Typing persists without manual action
  * Refresh restores content
  * Save indicator updates

**Task 7: Submit flow + required validation**

* **Goal:** Finalize attempt with timestamps/duration and lock it
* **Files:**

  * Runner component (`submit`)
  * `app/Livewire/Candidate/AssessmentComplete.php`
  * completion blade
* **Acceptance criteria:**

  * Missing required answers blocks submit with errors
  * Successful submit sets `completed_at`, `duration_seconds`, `status=submitted`
  * Completion page loads

---

### Phase 3 — Admin UI (list, detail, export)

**Task 8: Admin Basic Auth middleware**

* **Goal:** Protect /admin without full auth
* **Files:**

  * `app/Http/Middleware/AdminBasicAuth.php`
  * `app/Http/Kernel.php` (or Laravel 12 middleware registration)
  * `.env.example`
* **Acceptance criteria:**

  * /admin routes return 401 without creds
  * Works with correct creds

**Task 9: Admin attempts list**

* **Goal:** List/search/sort attempts
* **Files:**

  * `app/Livewire/Admin/AttemptsIndex.php`
  * `resources/views/livewire/admin/attempts-index.blade.php`
* **Acceptance criteria:**

  * Search by name/email
  * Shows status/started/completed/duration
  * Sort works (at least by started_at desc)

**Task 10: Admin attempt detail + notes + reviewed**

* **Goal:** View answers in order; store internal notes
* **Files:**

  * `app/Livewire/Admin/AttemptShow.php`
  * `resources/views/livewire/admin/attempt-show.blade.php`
* **Acceptance criteria:**

  * Answers shown grouped by section in correct order
  * Notes save
  * “Mark reviewed” sets `reviewed_at`

**Task 11: CSV export**

* **Goal:** Download submissions in one CSV row per attempt
* **Files:**

  * `app/Http/Controllers/Admin/ExportSubmissionsController.php`
  * route entry
* **CSV format (explicit):**

  * Columns:

    * `attempt_id` (internal)
    * `candidate_name`
    * `candidate_email`
    * `status`
    * `started_at`
    * `completed_at`
    * `duration_seconds`
    * then one column per question in config order: `answer__{question_key}`
* **Acceptance criteria:**

  * CSV downloads
  * Proper quoting for commas/newlines
  * Opens cleanly in Sheets/Excel

---

### Phase 4 — Tests + polish

**Task 12: Feature tests**

* **Goal:** Ensure core flows don’t regress
* **Files:**

  * `tests/Feature/StartAssessmentTest.php`
  * `tests/Feature/SubmitAssessmentTest.php`
  * `tests/Feature/AdminGateTest.php`
  * `tests/Feature/ExportTest.php`
* **Acceptance criteria:**

  * All tests pass in CI/local

**Task 13: Retention command (recommended)**

* **Goal:** Easy cleanup after hiring round
* **Files:**

  * `app/Console/Commands/PurgeOldAttempts.php`
  * scheduler registration
* **Acceptance criteria:**

  * Command deletes attempts older than configured days

---

If you want, I can also include “paste-ready” Blade layouts for the candidate runner and admin detail pages (still minimal, Tailwind-friendly), but the spec above should be enough for a straightforward build without introducing extra systems.
