<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Item;
use App\Models\RecurringSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users
        $fritz = User::create([
            'name' => 'Fritz',
            'email' => 'fritz@example.com',
            'password' => Hash::make('password'),
            'avatar_color' => '#4ECDC4',
            'role' => User::ROLE_PARENT,
        ]);

        $vreni = User::create([
            'name' => 'Vreni',
            'email' => 'vreni@example.com',
            'password' => Hash::make('password'),
            'avatar_color' => '#FF6B6B',
            'role' => User::ROLE_KID,
            'parent_id' => $fritz->id,
        ]);

        $ruedi = User::create([
            'name' => 'Ruedi',
            'email' => 'ruedi@example.com',
            'password' => Hash::make('password'),
            'avatar_color' => '#95E1D3',
            'role' => User::ROLE_KID,
            'parent_id' => $fritz->id,
        ]);

        // Get categories
        $dairy = Category::where('slug', 'dairy')->first();
        $bakery = Category::where('slug', 'bakery')->first();
        $produce = Category::where('slug', 'produce')->first();
        $beverages = Category::where('slug', 'beverages')->first();
        $other = Category::where('slug', 'other')->first();

        // Create items in inventory with recurring schedules
        $milch = Item::create([
            'name' => 'Milch',
            'quantity' => '1L',
            'category_id' => $dairy->id,
            'list_type' => Item::LIST_TYPE_INVENTORY,
            'created_by' => $fritz->id,
        ]);

        RecurringSchedule::create([
            'item_id' => $milch->id,
            'monday' => true,
            'wednesday' => true,
            'friday' => true,
        ]);

        $brot = Item::create([
            'name' => 'Brot',
            'quantity' => null,
            'category_id' => $bakery->id,
            'list_type' => Item::LIST_TYPE_INVENTORY,
            'created_by' => $vreni->id,
        ]);

        RecurringSchedule::create([
            'item_id' => $brot->id,
            'monday' => true,
            'tuesday' => true,
            'wednesday' => true,
            'thursday' => true,
            'friday' => true,
            'saturday' => true,
            'sunday' => true,
        ]);

        // Create items in shopping list
        Item::create([
            'name' => 'Butter',
            'quantity' => '250g',
            'category_id' => $dairy->id,
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $fritz->id,
        ]);

        Item::create([
            'name' => 'Joghurt',
            'quantity' => null,
            'category_id' => $dairy->id,
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $vreni->id,
        ]);

        Item::create([
            'name' => 'Äpfel',
            'quantity' => '6 Stück',
            'category_id' => $produce->id,
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $fritz->id,
        ]);

        // Create Quick Buy items
        Item::create([
            'name' => 'Red Bull',
            'quantity' => null,
            'category_id' => $beverages->id,
            'list_type' => Item::LIST_TYPE_QUICK_BUY,
            'created_by' => $vreni->id,
        ]);

        Item::create([
            'name' => 'Zigaretten',
            'quantity' => null,
            'category_id' => $other->id,
            'list_type' => Item::LIST_TYPE_QUICK_BUY,
            'created_by' => $fritz->id,
        ]);

        // Create some activities
        Activity::create([
            'user_id' => $fritz->id,
            'action' => Activity::ACTION_ITEM_ADDED,
            'subject_type' => 'Item',
            'subject_id' => 1,
            'subject_name' => 'Butter',
            'created_at' => now()->subMinutes(5),
        ]);

        Activity::create([
            'user_id' => $vreni->id,
            'action' => Activity::ACTION_QUICK_BUY_ADDED,
            'subject_type' => 'Item',
            'subject_id' => 2,
            'subject_name' => 'Red Bull',
            'created_at' => now()->subHours(2),
        ]);
    }
}
