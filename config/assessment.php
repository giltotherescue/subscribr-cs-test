<?php

return [
    'version' => '2025-12-11',
    'title' => 'Customer Support Lead — Assessment (Subscribr)',

    'admin' => [
        'username' => env('ASSESSMENT_ADMIN_USER', 'admin'),
        'password' => env('ASSESSMENT_ADMIN_PASS'),
    ],

    'retention_days' => env('ASSESSMENT_RETENTION_DAYS', 180),

    'sections' => [
        [
            'key' => 'email_responses',
            'title' => '1. Email Responses',
            'description' => 'Write the email responses as if you work at Subscribr.',
            'questions' => [
                [
                    'key' => 's1_q1a_angry_billing',
                    'title' => '1A. Angry billing and account issue',
                    'prompt' => <<<'PROMPT'
A customer writes:

"Hi, I cancelled my account last month but you still charged me. This is ridiculous. If this is not fixed right away I am going to dispute the charges with my bank."

Write the email response you would send as the Customer Support Lead at Subscribr.

Note: Subscribr's policy is to always refund accidental or disputed charges like this.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's1_q1b_loyal_blocked',
                    'title' => '1B. Loyal but blocked technical user',
                    'prompt' => <<<'PROMPT'
A long time customer writes:

"Hey Gil, I love Subscribr and I rely on it for all my YouTube scripts. For the last two days, my scripts keep timing out halfway through and I cannot get a full script to save. I have a video scheduled for tomorrow and I am getting really stressed. Can you help?"

Write the email response you would send.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
            ],
        ],

        [
            'key' => 'technical_debugging_symptoms',
            'title' => '2. Technical Debugging From Symptoms',
            'description' => 'You can reference https://subscribr.ai/help for this section.',
            'questions' => [
                [
                    'key' => 's2_q2a_research_limit',
                    'title' => '2A. Script research issue',
                    'prompt' => <<<'PROMPT'
A user reports:

"When I try to upload a PDF for research I get an error message. Why doesn't it work?"

Describe how you would diagnose and resolve this issue.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's2_q2b_wrong_language',
                    'title' => '2B. Script in wrong language',
                    'prompt' => <<<'PROMPT'
A user says:

"My scripts keep coming out in Spanish even though my channel is in English. I've tried everything but it won't stop."

How would you investigate and respond to the user?
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
            ],
        ],

        [
            'key' => 'debugging_from_logs',
            'title' => '3. Debugging From Logs',
            'description' => null,
            'questions' => [
                [
                    'key' => 's3_q3a_explain_log',
                    'title' => '3A. Explain what happened',
                    'prompt' => <<<'PROMPT'
A user reports: "My script started generating but got stuck around halfway. The loading spinner just keeps spinning."

You check the logs and find this sequence of events:

[14:53:09] INFO: Script generation started
  script_id: 12847
  run_id: 550e8400-e29b-41d4
  target_words: 2400
  status: running

[14:53:42] INFO: Section completed
  section: batch_1
  words_generated: 487
  total_words: 487

[14:54:18] INFO: Section completed
  section: batch_2
  words_generated: 512
  total_words: 999

[14:54:51] WARNING: Section generation failed
  section: batch_3
  attempt: 1
  error: "cURL error 28: Operation timed out after 30000 milliseconds"

[14:54:53] INFO: Retrying section
  section: batch_3
  attempt: 2

[14:55:26] WARNING: Section generation failed
  section: batch_3
  attempt: 2
  error: "cURL error 28: Operation timed out after 30000 milliseconds"

[14:55:26] ERROR: Section max retries exceeded
  section: batch_3
  total_attempts: 2
  status: failed

[14:55:26] ERROR: Script generation failed
  script_id: 12847
  error: "Section generation failed after 2 attempts"

In your own words, explain what these logs are telling you. What happened during the script generation, and why did it fail?
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's3_q3b_next_steps',
                    'title' => '3B. Next steps',
                    'prompt' => <<<'PROMPT'
Based on the logs above:

1. What would you tell the user about what happened?
2. What would you suggest they try?
3. Is there anything you would escalate or flag internally?
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
            ],
        ],

        [
            'key' => 'workflow_analysis',
            'title' => '4. Workflow Analysis',
            'description' => null,
            'questions' => [
                [
                    'key' => 's4_workflow',
                    'title' => '4. Workflow Analysis',
                    'prompt' => <<<'PROMPT'
Imagine a user flow where a creator:
• Connects their YouTube channel
• Imports existing videos
• Adds research documents
• Uses Script Bot to generate a new script
• Saves the script and sends it to their editor

Where in this workflow might users get confused or stuck? What would you suggest to improve the experience?
PROMPT,
                    'required' => true,
                    'rows' => 12,
                ],
            ],
        ],

        [
            'key' => 'code_reading_ai',
            'title' => '5. Code Reading and AI Assistance',
            'description' => 'You do not need to be a full time developer for this role, but you will sometimes look at code or use AI tools to help understand what is happening.',
            'questions' => [
                [
                    'key' => 's5_q5a_explain_code',
                    'title' => '5A. Explain the code',
                    'prompt' => <<<'PROMPT'
Below is a simplified Laravel function that handles research uploads:

public function uploadResearch(Request $request)
{
    $user = Auth::user();

    $file = $request->file('research');

    if (! $file) {
        return response()->json([
            'error' => 'No file uploaded',
        ], 400);
    }

    $allowedTypes = ['txt', 'pdf', 'doc', 'docx'];
    $extension = strtolower($file->getClientOriginalExtension());

    if (! in_array($extension, $allowedTypes)) {
        return response()->json([
            'error' => 'File type not supported',
        ], 400);
    }

    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file->getSize() > $maxSize) {
        return response()->json([
            'error' => 'File too large',
        ], 400);
    }

    $content = $file->get();

    if (strlen($content) > $user->research_limit) {
        return response()->json([
            'error' => 'Research limit exceeded',
        ], 400);
    }

    ResearchItem::create([
        'user_id' => $user->id,
        'content' => $content,
        'characters' => strlen($content),
    ]);

    return response()->json(['status' => 'ok']);
}

In your own words, explain what this function is doing.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's5_q5b_find_problems',
                    'title' => '5B. Find possible problems',
                    'prompt' => 'What issues might this code cause for users?',
                    'required' => true,
                    'rows' => 8,
                ],
            ],
        ],

        [
            'key' => 'live_troubleshooting',
            'title' => '6. Live Troubleshooting Simulation',
            'description' => 'Below is a mini conversation with a user. Pretend you are replying in a support inbox.',
            'questions' => [
                [
                    'key' => 's6_reply1',
                    'title' => '6. Reply 1',
                    'prompt' => <<<'PROMPT'
The user sends:

"Your tool just says Generating forever and never finishes. I have been trying for 20 minutes. Please fix this."

Write your first reply.
PROMPT,
                    'required' => true,
                    'rows' => 8,
                ],
                [
                    'key' => 's6_reply2',
                    'title' => '6. Reply 2',
                    'prompt' => <<<'PROMPT'
The user sends more information:

"It actually works on my second channel, just not on my main channel with all my older videos."

Write your second reply.
PROMPT,
                    'required' => true,
                    'rows' => 8,
                ],
                [
                    'key' => 's6_reply3',
                    'title' => '6. Reply 3',
                    'prompt' => <<<'PROMPT'
The user sends:

"Here is a screenshot of the screen where it is stuck."

(You can imagine the screenshot shows the Subscribr script page with a spinning loader and no error message.)

Write your third reply.
PROMPT,
                    'required' => true,
                    'rows' => 8,
                ],
                [
                    'key' => 's6_reply4',
                    'title' => '6. Reply 4 (Public Discord)',
                    'prompt' => <<<'PROMPT'
A different user posts in your Discord #help channel (visible to ~500 community members):

"Is anyone else having problems? Subscribr has been broken for me all day. Starting to regret paying for this."

Write your public reply.
PROMPT,
                    'required' => true,
                    'rows' => 8,
                ],
            ],
        ],

        [
            'key' => 'prioritization',
            'title' => '7. Prioritization and Grouping',
            'description' => null,
            'questions' => [
                [
                    'key' => 's7_prioritization',
                    'title' => '7. Prioritization and Grouping',
                    'prompt' => <<<'PROMPT'
Below are ten short issue summaries that came in on the same day:

1. "I got charged after my trial ended but I thought it was free."
2. "Channel Voice keeps giving me a voice that does not sound like my channel at all."
3. "Research upload says Research Limit Exceeded even on small files."
4. "I cannot find where to cancel my subscription."
5. "My script is stuck at Generating for more than 10 minutes."
6. "I would love if Subscribr could export scripts straight into Notion."
7. "My channel analytics look wrong. View counts are missing for some videos."
8. "I upgraded but still do not see the new features on my account."
9. "The email verification link does not work on mobile."
10. "I am not sure which plan is best for me. Can someone give me advice?"

How would you organize and prioritize these issues? Which would you handle first and why?
PROMPT,
                    'required' => true,
                    'rows' => 14,
                ],
            ],
        ],

        [
            'key' => 'help_center',
            'title' => '8. Help Center Article and AI Prompt',
            'description' => null,
            'questions' => [
                [
                    'key' => 's8_q8a_article',
                    'title' => '8A. Help Center article',
                    'prompt' => <<<'PROMPT'
Many users get confused by how credits work in Subscribr and what actions use them.

Write a short Help Center article titled "How Credits Work in Subscribr".
PROMPT,
                    'required' => true,
                    'rows' => 14,
                ],
                [
                    'key' => 's8_q8b_ai_prompt',
                    'title' => '8B. AI prompt',
                    'prompt' => <<<'PROMPT'
In your day-to-day work, you would use tools like ChatGPT or Claude to help draft help articles and internal docs.

Write the prompt you would give to an AI tool to help you create a first draft of the Help Center article above.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
            ],
        ],
    ],
];
