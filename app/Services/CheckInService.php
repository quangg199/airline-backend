<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\BoardingPass;
use App\Models\CheckIn;
use App\Models\Payment;
use Exception;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

/**
 * CheckInService
 * 
 * Xử lý tất cả logic liên quan đến Check-in:
 * - Tìm vé theo PNR và tên hành khách
 * - Kiểm tra trạng thái thanh toán
 * - Kiểm tra trạng thái chuyến bay (State Pattern)
 * - Tạo Boarding Pass với QR code
 * - Tạo bản ghi Check-in
 */
class CheckInService
{
    /**
     * Thực hiện Check-in cho hành khách
     * 
     * Luồng xử lý:
     * 1. Tìm Booking theo PNR code
     * 2. Tìm Ticket theo tên hành khách
     * 3. Kiểm tra Booking đã thanh toán chưa
     * 4. Kiểm tra chuyến bay có ở trạng thái CheckInState không
     * 5. Tạo BoardingPass nếu chưa có
     * 6. Tạo CheckIn record
     * 7. Trả về dữ liệu Boarding Pass
     * 
     * @param string $pnrCode - Mã đặt chỗ (6 ký tự)
     * @param string $passengerName - Tên hành khách
     * @return array - Dữ liệu Boarding Pass
     * @throws Exception - Nếu có lỗi trong quá trình Check-in
     */
    public function checkIn(string $pnrCode, string $passengerName): array
    {
        // 1. Tìm Booking theo PNR code
        $booking = Booking::where('pnr_code', strtoupper($pnrCode))->first();
        
        if (!$booking) {
            throw new Exception("Không tìm thấy mã đặt chỗ '{$pnrCode}'. Vui lòng kiểm tra lại.");
        }

        // 2. Tìm Ticket theo tên hành khách
        $ticket = $booking->tickets()
            ->where('passenger_name', 'LIKE', "%{$passengerName}%")
            ->first();

        if (!$ticket) {
            throw new Exception("Không tìm thấy hành khách tên '{$passengerName}' trong đơn đặt chỗ này.");
        }

        // 3. Kiểm tra Booking đã thanh toán chưa
        if ($booking->status !== 'paid') {
            throw new Exception("Đơn đặt chỗ chưa được thanh toán hoặc đã bị hủy. Trạng thái hiện tại: {$booking->status}");
        }

        // 4. Kiểm tra Payment status
        $payment = $booking->payment;
        if (!$payment || $payment->status !== 'success') {
            throw new Exception("Thanh toán chưa hoàn tất. Vui lòng thanh toán trước khi Check-in.");
        }

        // 5. Kiểm tra chuyến bay có ở trạng thái CheckInState không
        $flight = $booking->flight;

if (!$flight) {
    throw new Exception("Không tìm thấy chuyến bay.");
}

if ($ticket->checkIn) {
    throw new Exception("Hành khách đã check-in rồi.");
}

// TIME RULE
$now = now();

// đảm bảo Carbon (an toàn tuyệt đối)
$departure = \Carbon\Carbon::parse($flight->departure_time);

$checkInOpen = $departure->copy()->subHours(24);
$checkInClose = $departure->copy()->subHours(2);

if ($now->lt($checkInOpen)) {
    throw new Exception(
        "Check-in chưa mở. Mở vào: " . $checkInOpen->format('Y-m-d H:i')
    );
}

if ($now->gt($checkInClose)) {
    throw new Exception("Check-in đã đóng (trước giờ bay 2 tiếng).");
}

        // 6. Tạo BoardingPass nếu chưa có
        if (!$ticket->boardingPass) {
            $boardingPass = $this->generateBoardingPass($ticket);
        } else {
            $boardingPass = $ticket->boardingPass;
        }

        // 7. Tạo CheckIn record (ghi nhận hành khách đã Check-in vật lý)
        $checkIn = CheckIn::firstOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'boarding_gate' => null,
                'boarding_time' => now(),
                'is_boarded' => false,
            ]
        );

        // 8. Trả về dữ liệu Boarding Pass
        return [
            'boarding_pass_id' => $boardingPass->id,
            'ticket_code' => $ticket->ticket_code,
            'passenger_name' => $ticket->passenger_name,
            'pnr_code' => $booking->pnr_code,
            'flight_number' => $flight->flight_number,
            'departure_time' => is_string($flight->departure_time) 
                ? $flight->departure_time 
                : $flight->departure_time->format('Y-m-d H:i'),
            'seat_number' => $ticket->seat->seat_number ?? 'N/A',
            'qr_code_url' => $boardingPass->qr_code_url,
            'check_in_time' => is_string($checkIn->boarding_time) 
                ? $checkIn->boarding_time 
                : $checkIn->boarding_time->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Tạo Boarding Pass với QR code
     * 
     * @param Ticket $ticket
     * @return BoardingPass
     */
    private function generateBoardingPass(Ticket $ticket): BoardingPass
    {
        // Ghép dữ liệu thành chuỗi để mã hóa QR code
        $qrData = $this->generateQrData($ticket);

        // Sinh QR code dưới dạng SVG Base64 (không lưu vào disk)
        $qrCode = QrCode::format('svg')->generate($qrData);
        $qrBase64 = base64_encode($qrCode);
        $qrCodeUrl = "data:image/svg+xml;base64," . $qrBase64;

        // Tạo bản ghi Boarding Pass
        $boardingPass = BoardingPass::create([
            'ticket_id' => $ticket->id,
            'qr_code_url' => $qrCodeUrl,
            'gate' => 'TBA', // To Be Announced - sẽ được cập nhật sau
        ]);

        return $boardingPass;
    }

    /**
     * Tạo chuỗi dữ liệu cho QR code
     * Format: PNR|TênHànhKhách|ChuyếnBay|SốGhế
     * 
     * @param Ticket $ticket
     * @return string
     */
    private function generateQrData(Ticket $ticket): string
    {
        $booking = $ticket->booking;
        $flight = $ticket->flight;
        $seat = $ticket->seat;

        $pnrCode = $booking->pnr_code;
        $passengerName = $ticket->passenger_name;
        $flightNumber = $flight->flight_number;
        $seatNumber = $seat ? $seat->seat_number : 'TBA';

        return "{$pnrCode}|{$passengerName}|{$flightNumber}|{$seatNumber}";
    }

    /**
     * Kiểm tra xem hành khách đã Check-in chưa
     * 
     * @param string $pnrCode
     * @param string $passengerName
     * @return bool
     */
    public function hasCheckedIn(string $pnrCode, string $passengerName): bool
    {
        $ticket = Ticket::whereHas('booking', function ($query) use ($pnrCode) {
            $query->where('pnr_code', strtoupper($pnrCode));
        })
        ->where('passenger_name', 'LIKE', "%{$passengerName}%")
        ->first();

        if (!$ticket) {
            return false;
        }

        return $ticket->checkIn !== null;
    }

    /**
     * Khóa Booking - Ngăn khách hàng đổi chuyến hoặc mua thêm dịch vụ
     * 
     * @param Booking $booking
     * @return void
     */
    public function lockBookingAfterCheckIn(Booking $booking): void
    {
        // Thêm logic khóa tại đây nếu cần
        // Ví dụ: Đánh dấu booking là "checked_in" để ngăn chỉnh sửa
    }
}
