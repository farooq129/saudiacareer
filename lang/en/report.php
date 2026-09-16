<?php

/*
| The report-an-ad form. These keys are the `reason` column's values in the
| job_reports table, so adding a reason means one row there and one key here
| in each language.
*/

return [
    'title' => 'Report this ad',
    'reason' => 'Reason',
    'note' => 'Anything else we should know? (optional)',
    'submit' => 'Send report',

    'fake' => 'The job is not real',
    'fees' => 'Asks the applicant for money',
    'duplicate' => 'Duplicate ad',
    'filled' => 'The job is already filled',
    'offensive' => 'Offensive content',
    'wrong_category' => 'Listed in the wrong category',
    'other' => 'Something else',
];
