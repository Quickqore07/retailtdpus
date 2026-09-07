<?php

$docsPath = resource_path('js/views/onboarding-process/docs.json');
$docs = is_readable($docsPath)
    ? json_decode(file_get_contents($docsPath), true)
    : [];

return [
    'plans' => $docs['benefit_election_form'] ?? [],

    'supported_states' => ['PA', 'DE', 'NJ', 'MD', 'VA'],

    'state_names' => [
        'VA' => 'Virginia',
        'PA' => 'Pennsylvania',
        'NJ' => 'New Jersey',
        'MD' => 'Maryland',
        'DE' => 'Delaware',
    ],

    'state_name_to_code' => [
        'VIRGINIA' => 'VA',
        'PENNSYLVANIA' => 'PA',
        'NEW JERSEY' => 'NJ',
        'MARYLAND' => 'MD',
        'DELAWARE' => 'DE',
    ],
];
