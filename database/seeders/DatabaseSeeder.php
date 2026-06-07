<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\Flight;
use App\Models\Role;
use App\Models\Aircraft;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. TẠO CÁC ROLE (QUYỀN) TRƯỚC
        $adminRole = Role::create(['name' => 'admin']);
        $memberRole = Role::create(['name' => 'member']);

        // 2. TẠO TÀI KHOẢN ADMIN (Không để 'role' ở đây nữa)
        $admin = User::create([
            'name' => 'Quang Admin',
            'email' => 'admin@skylink.com',
            'password' => bcrypt('123456'),
            'membership_tier' => 'gold',
            'email_verified_at' => now(),
        ]);

        // GÁN QUYỀN ADMIN (Lưu vào bảng role_user)
        $admin->roles()->attach($adminRole);

        // 3. TẠO 10 USER MẪU VÀ GÁN QUYỀN MEMBER
        User::factory(10)->create()->each(function ($user) use ($memberRole) {
            $user->roles()->attach($memberRole);
        });

        // 4. GỌI CÁC SEEDER PHỤ TRÁCH TÀI NGUYÊN
        $this->call([
            AirportSeeder::class,
            AircraftSeeder::class, // File này sẽ tự đẻ ra cả SEATS (như anh đã hướng dẫn)
        ]);

        // 5. TẠO DỊCH VỤ BỔ TRỢ
        $services = [
            ['name' => 'Hành lý ký gửi 20kg', 'price' => 250000, 'type' => 1],
            ['name' => 'Hành lý ký gửi 30kg', 'price' => 400000, 'type' => 1],
            ['name' => 'Suất ăn nóng', 'price' => 120000, 'type' => 2],
            ['name' => 'Ưu tiên làm thủ tục', 'price' => 150000, 'type' => 3],
        ];
        foreach ($services as $service) {
            Service::create($service);
        }

        // 6. TẠO 50 CHUYẾN BAY MẪU
        // Lưu ý: FlightFactory cần có aircraft_id để không bị lỗi khóa ngoại
        Flight::factory(50)->create();

        // 7. TẠO 20 BOOKING MẪU (Dùng BookingFactory)
        \App\Models\Booking::factory(20)->create();
    }
}