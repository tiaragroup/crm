<?php

return [
    'disk'            => env('DOCUMENT_IMPORT_DISK', 'local'),
    'max_upload_kb'   => (int) env('DOCUMENT_IMPORT_MAX_KB', 20480),
    'max_rows'        => (int) env('DOCUMENT_IMPORT_MAX_ROWS', 100),
    'max_ai_chars'    => (int) env('DOCUMENT_IMPORT_MAX_AI_CHARS', 60000),
    'queue'           => filter_var(env('DOCUMENT_IMPORT_QUEUE', false), FILTER_VALIDATE_BOOL),
    'provider'        => 'anthropic',
    'api_key'         => env('ANTHROPIC_API_KEY'),
    'model'           => env('ANTHROPIC_DOCUMENT_MODEL', 'claude-sonnet-4-6'),
    'endpoint'        => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages'),
    'api_version'     => env('ANTHROPIC_API_VERSION', '2023-06-01'),
    'max_tokens'      => (int) env('ANTHROPIC_DOCUMENT_MAX_TOKENS', 8192),
    'timeout'         => (int) env('ANTHROPIC_DOCUMENT_TIMEOUT', 60),
];
