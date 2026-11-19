<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Models\User;
use App\Services\EmbeddingGenerator;
use App\Support\SemanticVectorSchema;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Tests\TestCase;

class SemanticSearchTextTest extends TestCase
{
    public function test_barang_semantic_text_includes_code_name_category_and_unit(): void
    {
        $barang = new Barang([
            'kode' => 'STR-001',
            'nama' => 'Beras Premium',
        ]);
        $barang->setAttribute('lokasi_rak', 'Rak A');
        $barang->setRelation('kategori', new KategoriBarang(['nama' => 'Sembako']));
        $barang->setRelation('satuan', new Satuan(['nama' => 'kg']));

        $this->assertSame('STR-001 Beras Premium Sembako kg', $barang->getSemanticText());
    }

    public function test_master_barang_semantic_text_includes_code_name_category_unit_and_description(): void
    {
        $masterBarang = new MasterBarang([
            'kode' => 'MB-001',
            'nama' => 'Beras Premium',
            'deskripsi' => 'Beras kualitas pilihan',
        ]);
        $masterBarang->setRelation('kategori', new KategoriBarang(['nama' => 'Sembako']));
        $masterBarang->setRelation('satuan', new Satuan(['nama' => 'Kilogram']));

        $this->assertSame(
            'MB-001 Beras Premium Sembako Kilogram Beras kualitas pilihan',
            $masterBarang->getSemanticText(),
        );
    }

    public function test_receipt_semantic_text_includes_supplier_rcv_and_item_conditions(): void
    {
        $detail = new PenerimaanBarangStoreDetail([
            'qty_diterima' => 10,
            'qty_rusak' => 2,
            'qty_kurang' => 1,
            'kondisi' => 'Rusak dan Kurang',
        ]);
        $barang = new Barang(['kode' => 'STR-001', 'nama' => 'Beras Premium']);
        $barang->setRelation('kategori', new KategoriBarang(['nama' => 'Sembako']));
        $detail->setRelation('barang', $barang);
        $detail->setRelation('satuan', new Satuan(['nama' => 'kg']));

        $receipt = new PenerimaanBarangStore([
            'nomor_penerimaan' => 'RCV-STR-28092026123-0001',
            'sumber_supplier' => 'PT Sumber Pangan',
        ]);
        $receipt->setRelation('details', new Collection([$detail]));

        $text = $receipt->getSemanticText();

        $this->assertStringContainsString('RCV-STR-28092026123-0001', $text);
        $this->assertStringContainsString('PT Sumber Pangan', $text);
        $this->assertStringContainsString('Beras Premium', $text);
        $this->assertStringContainsString('qty rusak 2', $text);
        $this->assertStringContainsString('Rusak dan Kurang', $text);
    }

    public function test_user_semantic_text_includes_name_and_username(): void
    {
        $user = new User([
            'name' => 'Budi Santoso',
            'username' => 'budi.santoso',
        ]);

        $this->assertSame(
            'Pengguna bernama Budi Santoso, dengan username budi.santoso.',
            $user->getSemanticText(),
        );
    }

    public function test_audit_log_semantic_text_excludes_change_payload_and_ip_address(): void
    {
        $auditLog = new AuditLog([
            'action' => 'updated',
            'description' => 'Mengubah User',
            'subject_type' => User::class,
            'ip_address' => '192.0.2.10',
            'changes' => ['sesudah' => ['password' => 'sensitive-value']],
        ]);
        $auditLog->setRelation('causer', new User([
            'name' => 'Budi Santoso',
            'username' => 'budi.santoso',
        ]));

        $text = $auditLog->getSemanticText();

        $this->assertStringContainsString('Budi Santoso', $text);
        $this->assertStringContainsString('updated', $text);
        $this->assertStringNotContainsString('192.0.2.10', $text);
        $this->assertStringNotContainsString('sensitive-value', $text);
    }

    public function test_vector_search_falls_back_when_database_does_not_support_pgvector(): void
    {
        $this->assertFalse(SemanticVectorSchema::hasVectorColumn('users'));
    }

    public function test_reindex_command_succeeds_without_pgvector_support(): void
    {
        config(['database.default' => 'sqlite']);

        $this->artisan('store:semantic-search-reindex')
            ->expectsOutput('Database bukan PostgreSQL; pencarian teks fallback tetap aktif dan tidak memerlukan reindex vector.')
            ->assertSuccessful();
    }

    public function test_embedding_generator_uses_laravel_ai_sdk_and_returns_pgvector_value(): void
    {
        config([
            'semantic_search.provider' => 'openai',
            'semantic_search.model' => 'text-embedding-3-small',
            'semantic_search.dimensions' => 1536,
            'semantic_search.cache_ttl' => 86400,
        ]);
        Embeddings::fake([[array_fill(0, 1536, 0.25)]]);

        $vector = app(EmbeddingGenerator::class)->generateVector('beras premium');

        self::assertSame(1536, count(explode(',', trim($vector, '[]'))));
        self::assertStringStartsWith('[0.25,0.25', $vector);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->contains('beras premium')
            && $prompt->dimensions === 1536
            && $prompt->model === 'text-embedding-3-small');
    }

    public function test_embedding_generator_rejects_an_invalid_provider_vector(): void
    {
        config([
            'semantic_search.provider' => 'openai',
            'semantic_search.model' => 'text-embedding-3-small',
            'semantic_search.dimensions' => 1536,
            'semantic_search.cache_ttl' => 0,
        ]);
        Embeddings::fake([[array_fill(0, 1535, 0.25)]]);

        $this->expectException(\RuntimeException::class);

        app(EmbeddingGenerator::class)->generateVector('beras premium');
    }
}
