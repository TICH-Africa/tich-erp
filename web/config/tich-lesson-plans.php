<?php

return [
    'upload' => [
        'max_kb' => 10240,
        'allowed_extensions' => ['pdf', 'doc', 'docx'],
        'allowed_mimes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    'form_fields' => [
        'general_objective' => 'General objective',
        'prior_knowledge' => 'Prior knowledge / entry behaviour',
        'references' => 'References',
        'assignment' => 'Assignment / homework',
        'venue' => 'Venue',
        'session_time' => 'Session time',
        'intake_class' => 'Class / intake',
    ],

    'session_row_columns' => [
        'time' => 'Time',
        'content' => 'Content',
        'trainer_activities' => 'Trainers activities',
        'learner_activities' => 'Learners activities',
        'evaluation' => 'Evaluation / assessment',
    ],
];
