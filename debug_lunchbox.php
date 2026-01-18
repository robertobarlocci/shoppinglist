<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\LunchboxItem;
use App\Models\User;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Setup in-memory sqlite
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'foreign_key_constraints' => true,
]]);

// Run migrations manually (simplified)
Schema::create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('password');
    $table->string('avatar_color')->nullable();
    $table->string('role')->default('parent');
    $table->foreignId('parent_id')->nullable();
    $table->timestamps();
});

Schema::create('lunchbox_items', function ($table) {
    $table->id();
    $table->foreignId('user_id');
    $table->date('date');
    $table->string('item_name');
    $table->timestamps();
});

// Create Data
$parent = User::create([
    'name' => 'Parent',
    'email' => 'parent@example.com',
    'password' => 'password',
    'role' => UserRole::PARENT,
]);

$kid = User::create([
    'name' => 'Kid',
    'email' => 'kid@example.com',
    'password' => 'password',
    'role' => UserRole::KID,
    'parent_id' => $parent->id,
]);

// Kid creates item
$date = Carbon::now()->startOfWeek()->addDays(2); // Wednesday
$item = LunchboxItem::create([
    'user_id' => $kid->id,
    'date' => $date,
    'item_name' => 'Test Item',
]);

echo 'Created Item on: ' . $date->format('Y-m-d') . "\n";
echo 'Kid ID: ' . $kid->id . "\n";
echo 'Parent ID: ' . $parent->id . "\n";
echo 'Kid Parent ID: ' . $kid->parent_id . "\n";

// Parent Query Logic
$startDate = Carbon::now()->startOfWeek();
$endDate = $startDate->copy()->endOfWeek();

echo 'Query Start: ' . $startDate->format('Y-m-d') . "\n";
echo 'Query End: ' . $endDate->format('Y-m-d') . "\n";

$childIds = $parent->children()->pluck('id')->toArray();
echo 'Child IDs found: ' . implode(', ', $childIds) . "\n";

$query = LunchboxItem::with('user')
    ->whereBetween('date', [$startDate, $endDate]);

$query->whereIn('user_id', $childIds);

$results = $query->get();

echo 'Results found: ' . $results->count() . "\n";

foreach ($results as $res) {
    echo ' - ' . $res->item_name . ' (' . $res->date->format('Y-m-d') . ")\n";
}
