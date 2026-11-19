<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class AiSearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Builder::macro('whereVectorSimilarTo', function ($column, $embedding) {
            if (! is_string($column) || ! preg_match('/^[a-z_][a-z0-9_]*(\.[a-z_][a-z0-9_]*)?$/i', $column)) {
                throw new \InvalidArgumentException('Kolom vector search tidak valid.');
            }

            $this->orderByRaw("$column <=> ?::vector", [$embedding]);

            return $this;
        });
    }
}
