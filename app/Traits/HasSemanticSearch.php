<?php

namespace App\Traits;

use App\Jobs\GenerateVectorEmbedding;
use App\Support\SemanticVectorSchema;

trait HasSemanticSearch
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSemanticSearch()
    {
        static::saved(function ($model) {
            // Kita ingin memicu pembuatan vektor secara asynchronous setiap kali model disimpan.
            // Model harus mengimplementasikan method `getSemanticText()`
            if (method_exists($model, 'getSemanticText')
                && SemanticVectorSchema::hasVectorColumn($model->getTable())) {
                $text = $model->getSemanticText();

                // Dispatch job ke background
                GenerateVectorEmbedding::dispatch($model, $text)->afterCommit();
            }
        });
    }
}
