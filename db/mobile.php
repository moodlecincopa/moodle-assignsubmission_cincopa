<?PHP

$addons = [
    'assignsubmission_cincopa' => [ // Plugin identifier
        'handlers' => [ // Different places where the plugin will display content.
            'submissionHandler' => [ // Handler unique name (alphanumeric).
                'delegate' => 'AddonModAssignSubmissionDelegate', // Delegate (where to display the link to the plugin)
                'method' => 'mobile_submission_edit', // Main function in \mod_certificate\output\mobile
            ],
        ],
        'lang' => [ // Language strings that are used in all the handlers.
            ['pluginname', 'assignsubmission_cincopa'],
            ['cincopa', 'assignsubmission_cincopa'],
            ['default', 'assignsubmission_cincopa'],
            ['default_help', 'assignsubmission_cincopa'],
            ['enabled', 'assignsubmission_cincopa'],
            ['enabled_help', 'assignsubmission_cincopa'],
            ['template_cincopa', 'assignsubmission_cincopa'],
            ['template_cincopa_help', 'assignsubmission_cincopa'],
            ['api_token_cincopa', 'assignsubmission_cincopa'],
            ['api_token_cincopa_help', 'assignsubmission_cincopa'],
            ['description', 'assignsubmission_cincopa'],
        ],
    ],
];