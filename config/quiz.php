<?php

return [
    // Cooldown per person and certificate type.
    'cooldown_days' => env('QUIZ_COOLDOWN_DAYS', 30),

    // Anti-spam short window for repeated start requests.
    'start_rate_limit_minutes' => env('QUIZ_START_RATE_LIMIT_MINUTES', 2),

    // Passing score threshold used to assign result type.
    'pass_score_percentage' => env('QUIZ_PASS_SCORE_PERCENTAGE', 66.67),
];
