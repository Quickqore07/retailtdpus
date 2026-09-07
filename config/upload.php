<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum upload file size (kilobytes)
    |--------------------------------------------------------------------------
    |
    | Laravel's max rule expects kilobytes. 5120 KB = 5 MB.
    |
    */
    'max_file_size_kb' => (int) env('UPLOAD_MAX_FILE_SIZE_KB', 10240),

];
