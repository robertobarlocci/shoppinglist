<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'produce',
                'name' => 'Obst & Gemüse',
                'color' => '#4CAF50',
                'icon' => 'carrot',
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'dairy',
                'name' => 'Milchprodukte',
                'color' => '#2196F3',
                'icon' => 'milk',
                'is_default' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'bakery',
                'name' => 'Backwaren',
                'color' => '#FF9800',
                'icon' => 'croissant',
                'is_default' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'meat',
                'name' => 'Fleisch',
                'color' => '#F44336',
                'icon' => 'beef',
                'is_default' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'fish',
                'name' => 'Fisch',
                'color' => '#00BCD4',
                'icon' => 'fish',
                'is_default' => true,
                'sort_order' => 5,
            ],
            [
                'slug' => 'frozen',
                'name' => 'Tiefkühlkost',
                'color' => '#9C27B0',
                'icon' => 'snowflake',
                'is_default' => true,
                'sort_order' => 6,
            ],
            [
                'slug' => 'pantry',
                'name' => 'Vorrat',
                'color' => '#795548',
                'icon' => 'package',
                'is_default' => true,
                'sort_order' => 7,
            ],
            [
                'slug' => 'beverages',
                'name' => 'Getränke',
                'color' => '#E91E63',
                'icon' => 'wine',
                'is_default' => true,
                'sort_order' => 8,
            ],
            [
                'slug' => 'cleaning',
                'name' => 'Putzmittel',
                'color' => '#00BCD4',
                'icon' => 'spray',
                'is_default' => true,
                'sort_order' => 9,
            ],
            [
                'slug' => 'pharmacy',
                'name' => 'Apotheke',
                'color' => '#F44336',
                'icon' => 'pill',
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'other',
                'name' => 'Sonstiges',
                'color' => '#9E9E9E',
                'icon' => 'box',
                'is_default' => true,
                'sort_order' => 11,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
