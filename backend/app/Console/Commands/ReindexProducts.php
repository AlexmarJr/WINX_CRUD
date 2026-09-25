<?php

namespace App\Console\Commands;

use App\Repositories\ProductSearchRepository;
use Illuminate\Console\Command;

class ReindexProducts extends Command
{
    protected $signature = 'products:reindex';

    protected $description = 'Reconstrói o índice de busca a partir dos produtos ativos do PostgreSQL';

    public function handle(ProductSearchRepository $searchRepository): int
    {
        $count = $searchRepository->rebuild();
        $this->info("{$count} produto(s) indexado(s).");

        return self::SUCCESS;
    }
}
