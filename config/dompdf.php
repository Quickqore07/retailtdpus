<?php

return [

    'show_warnings' => false,   // Throw an Exception on warnings from dompdf

    'public_path' => null,  // Override the public path if needed

    'options' => [

        'font_dir' => storage_path('fonts/'), // 👈 IMPORTANT (moved from vendor)
        'font_cache' => storage_path('fonts/'),

        'temp_dir' => sys_get_temp_dir(),

        'chroot' => realpath(base_path()),

        'allowed_protocols' => [
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,

        'log_output_file' => null,

        'enable_font_subsetting' => false,

        'pdf_backend' => 'CPDF',

        'default_media_type' => 'screen',

        'default_paper_size' => 'a4',

        'default_paper_orientation' => 'portrait',

        'default_font' => 'sans-serif',

        'dpi' => 96,

        'enable_php' => false,

        'enable_javascript' => true,

        'enable_remote' => true, // 👈 required if loading images from URL

        'allowed_remote_hosts' => null,

        'font_height_ratio' => 1.1,

        'enable_html5_parser' => true,
    ],

];