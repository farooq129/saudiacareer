<?php

/*
| The AI tools menu. Tool keys live in config/board.php; the copy lives here.
*/

return [
    'nav' => 'AI tools',
    'badge' => 'New',
    'tools' => [
        'cv' => [
            'title' => 'CV optimiser',
            'body' => 'Score your CV and get a rewrite that clears applicant tracking systems',
        ],
        'match' => [
            'title' => 'Smart job match',
            'body' => 'Jobs ranked by your skills and experience, not by posting date',
        ],
        'letter' => [
            'title' => 'Instant cover letter',
            'body' => 'A letter tailored to any job in under a minute',
        ],
    ],
];
