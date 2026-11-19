<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Laravel\Ai\Embeddings;
use RuntimeException;

final class EmbeddingGenerator
{
    public function generateVector(string $text): string
    {
        if (trim($text) === '') {
            throw new RuntimeException('Teks untuk embedding tidak boleh kosong.');
        }

        $dimensions = (int) config('semantic_search.dimensions', 1536);
        $provider = (string) config('semantic_search.provider', 'openai');
        $model = (string) config('semantic_search.model', 'text-embedding-3-small');
        $cacheTtl = (int) config('semantic_search.cache_ttl', 86400);
        $timeout = (int) config('semantic_search.timeout', 5);
        if ($dimensions < 1 || $timeout < 1 || $cacheTtl < 0 || $provider === '' || $model === '') {
            throw new InvalidArgumentException('Konfigurasi provider embedding tidak valid.');
        }

        $cacheKey = 'semantic_vector:'.hash('sha256', $provider.':'.$model.':'.$dimensions.':'.$text);

        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $response = Embeddings::for([$text])
            ->dimensions($dimensions)
            ->cache($cacheTtl, individually: true)
            ->timeout($timeout)
            ->generate(provider: $provider, model: $model);

        $embedding = $response->first();
        if (count($embedding) !== $dimensions) {
            throw new RuntimeException('Penyedia embedding mengembalikan vektor dengan dimensi yang tidak valid.');
        }

        foreach ($embedding as $value) {
            if (! is_finite($value)) {
                throw new RuntimeException('Penyedia embedding mengembalikan nilai vektor yang tidak valid.');
            }
        }

        $vectorString = '['.implode(',', $embedding).']';

        if ($cacheTtl > 0) {
            Cache::put($cacheKey, $vectorString, now()->addSeconds($cacheTtl));
        }

        return $vectorString;
    }
}
