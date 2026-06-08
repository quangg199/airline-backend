<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class AircraftSeeder extends Seeder
{
    public function run(): void
    {
        $aircrafts = [
            ['tail_number' => 'VN-B787', 'model' => 'Boeing 787', 'status' => 1],
            ['tail_number' => 'VJ-A320', 'model' => 'Airbus A320neo', 'status' => 1],
            ['tail_number' => 'FB-E190', 'model' => 'Embraer 190', 'status' => 1],
        ];

        foreach ($aircrafts as $data) {
            $aircraft = Aircraft::create($data);

            // 2. Logic đẻ ra 180 ghế (30 hàng, mỗi hàng 6 ghế A->F)
            $seatsData = [];
            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
            
            for ($row = 1; $row <= 30; $row++) {
                foreach ($letters as $letter) {
                    // Hàng 1-5 là Business (Hạng 2), còn lại là Economy (Hạng 1)
                    $seatClass = ($row <= 5) ? 2 : 1; 
                    
                    $seatsData[] = [
                        'aircraft_id' => $aircraft->id,
                        'seat_number' => $row . $letter,
                        'seat_class' => $seatClass,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // 3. Bulk Insert
            Seat::insert($seatsData); 
        }
    }
}