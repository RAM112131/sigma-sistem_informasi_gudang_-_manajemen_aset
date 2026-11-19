<?php

return [
    'provider' => env('AI_EMBEDDINGS_PROVIDER', 'openai'),
    'model' => env('AI_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
    'dimensions' => (int) env('AI_EMBEDDINGS_DIMENSIONS', 1536),
    'timeout' => (int) env('AI_EMBEDDINGS_TIMEOUT', 5),
    'cache_ttl' => (int) env('AI_EMBEDDINGS_CACHE_TTL', 86400),
    'distance_threshold' => (float) env('AI_EMBEDDINGS_DISTANCE_THRESHOLD', 0.65),
];
