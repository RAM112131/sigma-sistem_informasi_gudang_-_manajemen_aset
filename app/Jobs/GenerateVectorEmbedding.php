<?php

namespace App\Jobs;

use App\Services\EmbeddingGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class GenerateVectorEmbedding implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    protected Model $model;

    protected $textToEmbed;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $model, string $textToEmbed)
    {
        $this->model = $model;
        $this->textToEmbed = $textToEmbed;
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingGenerator $embeddingGenerator): void
    {
        if (trim($this->textToEmbed) === '') {
            return;
        }

        $this->model->embedding_search = $embeddingGenerator->generateVector($this->textToEmbed);
        $this->model->saveQuietly();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
