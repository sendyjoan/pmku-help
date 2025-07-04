<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    private array $data = [
        [
            'name' => 'Todo',
            'color' => '#cecece',
            'is_default' => true,
            'order' => 1
        ],
        [
            'name' => 'In progress',
            'color' => '#ff7f00',
            'is_default' => false,
            'order' => 2
        ],
        [
            'name' => 'Pending Review',
            'color' => '#fffc00',
            'is_default' => false,
            'order' => 3
        ],
        [
            'name' => 'In Review',
            'color' => '#00ff75',
            'is_default' => false,
            'order' => 4
        ],
        [
            'name' => 'Pending UAT',
            'color' => '#00ffff',
            'is_default' => false,
            'order' => 5
        ],
        [
            'name' => 'In UAT',
            'color' => '#007be0',
            'is_default' => false,
            'order' => 6
        ],
        [
            'name' => 'Done',
            'color' => '#008000',
            'is_default' => false,
            'order' => 7
        ],
        [
            'name' => 'Archived',
            'color' => '#ff0000',
            'is_default' => false,
            'order' => 8
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
            TicketStatus::firstOrCreate($item);
        }
    }
}
