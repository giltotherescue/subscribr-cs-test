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
                    'title' => '2A. Research limit issue',
                    'prompt' => <<<'PROMPT'
A user reports:

"When I try to upload research I get a message saying Research Limit Exceeded. My file only has about 9,500 words and my plan says I can have 50,000 words of research. Why is this happening and how do I fix it?"

Describe how you would diagnose and resolve this issue.
PROMPT,
                    'required' => true,
                    'rows' => 10,
                ],
                [
                    'key' => 's2_q2b_short_scripts',
                    'title' => '2B. Short or cut off scripts',
                    'prompt' => <<<'PROMPT'
A user says:

"No matter what I do, the script stops early. I ask for a 2,000 word script and it stops around 900 to 1,000 words every time."

What might be causing this? How would you investigate?
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
You are given this log excerpt from a script generation job:

[2025-12-02 14:53:09] production.ERROR:
OpenAI API Error: RateLimitExceededException
Model: gpt-5-large
Job: GenerateScript
User ID: 29193
Script length requested: 3000 words
Queue: high
Trace: ScriptJobHandler.php:83

In your own words, explain what this log is telling you and what probably happened when the user tried to generate their script.
PROMPT,
                    'required' => true,
                    'rows' => 8,
                ],
                [
                    'key' => 's3_q3b_next_steps',
                    'title' => '3B. Next steps',
                    'prompt' => 'What would you do next to investigate and resolve this?',
                    'required' => true,
                    'rows' => 8,
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
Below is a simple Laravel style function that handles research uploads:

public function uploadResearch(Request $request)
{
    $user = Auth::user();

    $file = $request->file('research');

    if (! $file) {
        return response()->json([
            'error' => 'No file uploaded',
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
                    'rows' => 8,
                ],
                [
                    'key' => 's5_q5b_find_problems',
                    'title' => '5B. Find possible problems',
                    'prompt' => 'What issues might this code cause for users?',
                    'required' => true,
                    'rows' => 8,
                ],
                [
                    'key' => 's5_q5c_using_ai',
                    'title' => '5C. Using AI to help',
                    'prompt' => 'How would you use AI tools to help you understand code like this in a support role?',
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

How would you organize, prioritize, and handle these issues?
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
