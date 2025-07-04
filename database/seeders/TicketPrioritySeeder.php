<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    private array $data = [
        [
            'name' => 'Low',
            'color' => '#008000',
            'is_default' => true
        ],
        [
            'name' => 'Low to Medium',
            'color' => '#b5ff00',
            'is_default' => false
        ],
        [
            'name' => 'Medium',
            'color' => '#fff500',
            'is_default' => false
        ],
        [
            'name' => 'Medium to High',
            'color' => '#ff8a00',
            'is_default' => false
        ],
        [
            'name' => 'High',
            'color' => '#ff0000',
            'is_default' => false
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data as $item) {
            TicketPriority::firstOrCreate($item);
        }
    }
}
