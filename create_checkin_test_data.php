<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Airport;
use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    // 1. Lấy hoặc tạo Airport
    $han = Airport::firstOrCreate(
        ['code' => 'HAN'],
        ['name' => 'Noi Bai International Airport', 'city' => 'Hanoi']
    );

    $sgn = Airport::firstOrCreate(
        ['code' => 'SGN'],
        ['name' => 'Tan Son Nhat International Airport', 'city' => 'Ho Chi Minh City']
    );

    // 2. Lấy hoặc tạo Aircraft
    $aircraft = Aircraft::firstOrCreate(
        ['tail_number' => 'VN-A321-TEST'],
        ['model' => 'Airbus A321', 'status' => 1]
    );

    // Tạo Seat nếu chưa có
    $seat = Seat::firstOrCreate(
        ['aircraft_id' => $aircraft->id, 'seat_number' => '12A'],
        ['seat_class' => 'economy', 'status' => 'available']
    );

    // 3. Tạo Flight nằm trong khung giờ check-in (hiện tại + 10 giờ, tức là trong khoảng 2h - 24h trước khi bay)
    $flightNumber = 'VN' . rand(100, 999);
    $flight = Flight::create([
        'flight_number' => $flightNumber,
        'departure_airport_id' => $han->id,
        'arrival_airport_id' => $sgn->id,
        'aircraft_id' => $aircraft->id,
        'departure_time' => Carbon::now()->addHours(10),
        'arrival_time' => Carbon::now()->addHours(12),
        'base_price' => 1200000,
        'available_seats' => 180,
        'status' => 'scheduled',
    ]);

    // 4. Lấy hoặc tạo User
    $user = User::first() ?: User::create([
        'name' => 'Test Passenger',
        'email' => 'passenger@skylink.com',
        'password' => bcrypt('password'),
    ]);

    // 5. Xóa booking cũ có PNR TESTCK nếu trùng để tránh lỗi trùng khóa độc nhất
    $oldBookings = Booking::where('pnr_code', 'TESTCK')->get();
    foreach ($oldBookings as $oldBk) {
        $oldBk->tickets()->delete();
        $oldBk->payment()->delete();
        $oldBk->delete();
    }

    // 6. Tạo Booking
    $booking = Booking::create([
        'user_id' => $user->id,
        'flight_id' => $flight->id,
        'pnr_code' => 'TESTCK',
        'total_amount' => 1200000,
        'status' => 'paid',
    ]);

    // 7. Tạo Payment thành công
    $payment = Payment::create([
        'booking_id' => $booking->id,
        'amount' => 1200000,
        'payment_method' => 'vnpay',
        'status' => 'success',
        'transaction_id' => 'TX' . time(),
    ]);

    // 8. Tạo Ticket
    $ticket = Ticket::create([
        'booking_id' => $booking->id,
        'flight_id' => $flight->id,
        'seat_id' => $seat->id,
        'passenger_name' => 'NGUYEN VAN A',
        'identity_number' => 'ID999999',
        'ticket_code' => 'T' . rand(1000000, 9999999),
        'ticket_price' => 1200000,
    ]);

    DB::commit();

    echo "\n==================================================\n";
    echo " TẠO DỮ LIỆU TEST CHECK-IN THÀNH CÔNG!\n";
    echo "==================================================\n";
    echo "Mã đặt chỗ (PNR Code) : TESTCK\n";
    echo "Họ và tên hành khách  : NGUYEN VAN A\n";
    echo "Chuyến bay            : {$flight->flight_number} ({$han->code} -> {$sgn->code})\n";
    echo "Thời gian khởi hành   : " . $flight->departure_time->format('Y-m-d H:i') . "\n";
    echo "Trạng thái chuyến bay : {$flight->status}\n";
    echo "==================================================\n";
    echo "HƯỚNG DẪN TEST:\n";
    echo "1. Mở trình duyệt truy cập trang Check-in:\n";
    echo "   http://localhost:5173/check-in\n";
    echo "2. Nhập Mã đặt chỗ (PNR): TESTCK\n";
    echo "3. Nhập Họ và tên: NGUYEN VAN A\n";
    echo "4. Tích chọn các cam kết an toàn và điều khoản.\n";
    echo "5. Bấm \"Check-in Ngay\" để xem kết quả!\n";
    echo "==================================================\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "LỖI KHI TẠO DỮ LIỆU TEST: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
