<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()
            ->where('role', 'admin')
            ->whereNotNull('tenancy_id')
            ->oldest()
            ->firstOrFail();

        DB::transaction(function () use ($user): void {
            $categories = [
                'Periféricos' => 'Mouses, teclados e acessórios de entrada.',
                'Monitores' => 'Telas para estações de trabalho.',
                'Áudio' => 'Headsets e equipamentos de som.',
                'Acessórios' => 'Cabos, adaptadores e suportes.',
            ];

            foreach ($categories as $name => $description) {
                Category::query()->firstOrCreate(
                    ['tenancy_id' => $user->tenancy_id, 'name' => $name],
                    ['user_id' => $user->id, 'description' => $description, 'status' => 'active'],
                );
            }

            $categoryIds = Category::query()
                ->where('tenancy_id', $user->tenancy_id)
                ->whereIn('name', array_keys($categories))
                ->pluck('id', 'name');

            $products = [
                ['Mouse sem fio M185', 'Periféricos', 'Mouse sem fio com conexão USB.', '49.90', '89.90', 2],
                ['Teclado mecânico K500', 'Periféricos', 'Teclado mecânico compacto.', '159.00', '249.00', 3],
                ['Monitor LED 24 polegadas', 'Monitores', 'Monitor Full HD.', '620.00', '899.00', 5],
                ['Headset USB Pro', 'Áudio', 'Headset com microfone.', '115.00', '189.00', 7],
                ['Cabo HDMI 2 m', 'Acessórios', 'Cabo HDMI de alta velocidade.', '18.00', '39.90', 9],
                ['Hub USB-C 6 portas', 'Acessórios', 'Hub com seis conexões.', '95.00', '169.00', 12],
                ['Caixa de som compacta', 'Áudio', 'Caixa de som para mesa.', '75.00', '129.00', 18],
                ['Suporte para notebook', 'Acessórios', 'Suporte ajustável de mesa.', '42.00', '79.00', 25],
            ];

            foreach ($products as [$name, $categoryName, $description, $cost, $price, $stock]) {
                Product::query()->firstOrCreate(
                    ['tenancy_id' => $user->tenancy_id, 'name' => $name],
                    [
                        'user_id' => $user->id,
                        'category_id' => $categoryIds[$categoryName],
                        'description' => $description,
                        'cost' => $cost,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => 'active',
                    ],
                );
            }
        });
    }
}
