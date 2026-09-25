<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenancy;
use App\Models\User;

class StarterInventoryService
{
    /** @var array<string, array{description: string, products: array<int, array{string, int, int}>}> */
    private const CATALOG = [
        'Periféricos' => [
            'description' => 'Dispositivos de entrada e acessórios para computadores.',
            'products' => [
                ['Mouse sem fio M185', 8990, 12],
                ['Mouse gamer RGB G203', 15990, 8],
                ['Teclado mecânico K500', 24990, 4],
                ['Teclado compacto sem fio', 18990, 0],
                ['Webcam Full HD W200', 21990, 9],
                ['Mousepad grande', 5990, 17],
                ['Controle USB para PC', 14990, 6],
                ['Leitor de cartão USB', 4990, 15],
                ['Apresentador sem fio', 12990, 3],
                ['Scanner de mesa compacto', 69990, 2],
            ],
        ],
        'Monitores' => [
            'description' => 'Telas para trabalho, criação e jogos.',
            'products' => [
                ['Monitor LED 21 polegadas', 69990, 8],
                ['Monitor LED 24 polegadas', 89990, 5],
                ['Monitor IPS 27 polegadas', 129990, 0],
                ['Monitor ultrawide 29 polegadas', 169990, 2],
                ['Monitor gamer 24 polegadas', 119990, 4],
                ['Monitor gamer 27 polegadas', 189990, 3],
                ['Monitor portátil 15 polegadas', 99990, 7],
                ['Monitor 4K 28 polegadas', 229990, 1],
                ['Monitor curvo 32 polegadas', 199990, 2],
                ['Monitor profissional 25 polegadas', 149990, 6],
            ],
        ],
        'Áudio' => [
            'description' => 'Fones, microfones e caixas de som.',
            'products' => [
                ['Headset USB Pro', 18990, 7],
                ['Headset Bluetooth Office', 29990, 4],
                ['Microfone USB M100', 22990, 10],
                ['Caixa de som compacta', 12990, 18],
                ['Caixa de som Bluetooth', 24990, 0],
                ['Fone intra-auricular', 7990, 20],
                ['Soundbar para monitor', 34990, 3],
                ['Microfone de mesa M200', 39990, 2],
                ['Interface de áudio USB', 59990, 1],
                ['Suporte articulado para microfone', 11990, 9],
            ],
        ],
        'Armazenamento' => [
            'description' => 'Unidades e acessórios para armazenamento de dados.',
            'products' => [
                ['SSD SATA 240 GB', 17990, 12],
                ['SSD SATA 480 GB', 27990, 5],
                ['SSD NVMe 1 TB', 49990, 4],
                ['HD externo 1 TB', 38990, 6],
                ['HD externo 2 TB', 58990, 2],
                ['Pen drive USB 64 GB', 5990, 0],
                ['Pen drive USB 128 GB', 8990, 17],
                ['Cartão microSD 128 GB', 9990, 11],
                ['Case para SSD 2.5 polegadas', 6990, 8],
                ['Dock para HD USB 3.0', 19990, 3],
            ],
        ],
        'Acessórios' => [
            'description' => 'Cabos, suportes e utilidades para o escritório.',
            'products' => [
                ['Cabo HDMI 2 m', 3990, 25],
                ['Cabo USB-C 1 m', 2990, 30],
                ['Hub USB-C 6 portas', 16990, 12],
                ['Suporte para notebook', 7990, 20],
                ['Adaptador USB-C para HDMI', 9990, 15],
                ['Carregador USB-C 65 W', 18990, 7],
                ['Organizador de cabos', 2490, 0],
                ['Base refrigerada para notebook', 14990, 6],
                ['Filtro de linha 6 tomadas', 6990, 16],
                ['Mochila para notebook 15 polegadas', 22990, 5],
            ],
        ],
    ];

    public function createFor(Tenancy $tenancy, User $owner): void
    {
        foreach (self::CATALOG as $categoryName => $details) {
            $category = Category::query()->create([
                'tenancy_id' => $tenancy->id,
                'user_id' => $owner->id,
                'name' => $categoryName,
                'description' => $details['description'],
                'status' => CategoryStatus::Active->value,
            ]);

            foreach ($details['products'] as [$name, $priceCents, $stock]) {
                Product::query()->create([
                    'tenancy_id' => $tenancy->id,
                    'user_id' => $owner->id,
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => $name.' para uso na empresa.',
                    'cost' => number_format(intdiv($priceCents * 7, 10) / 100, 2, '.', ''),
                    'price' => number_format($priceCents / 100, 2, '.', ''),
                    'stock' => $stock,
                    'status' => ProductStatus::Active->value,
                ]);
            }
        }
    }
}
