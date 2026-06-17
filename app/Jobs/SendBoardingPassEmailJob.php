<?php

namespace App\Jobs;

use App\Models\BoardingPass;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SendBoardingPassEmailJob
 * 
 * Job được queue (xếp hàng) để gửi Thẻ lên máy bay (Boarding Pass) qua email
 * mà không làm chậm trang web khi người dùng Check-in.
 * 
 * Được triển khai sau khi Check-in thành công.
 * Sử dụng Laravel Queue để xử lý ngầm (background job).
 */
class SendBoardingPassEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Số lần retry nếu job thất bại
     * @var int
     */
    public int $tries = 3;

    /**
     * Thời gian chờ giữa các lần retry (giây)
     * @var int
     */
    public int $backoff = 60;

    /**
     * ID của Boarding Pass cần gửi
     * @var int
     */
    private int $boardingPassId;

    /**
     * Constructor
     * 
     * @param int $boardingPassId
     */
    public function __construct(int $boardingPassId)
    {
        $this->boardingPassId = $boardingPassId;
    }

    /**
     * Xử lý job - Gửi email Boarding Pass
     * 
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            // 1. Lấy Boarding Pass
            $boardingPass = BoardingPass::with(['ticket.booking', 'ticket.flight', 'ticket.seat'])
                ->find($this->boardingPassId);

            if (!$boardingPass) {
                throw new Exception("Không tìm thấy Boarding Pass với ID: {$this->boardingPassId}");
            }

            $ticket = $boardingPass->ticket;
            $booking = $ticket->booking;
            $flight = $ticket->flight;
            $user = $booking->user;

            // 2. Tạo dữ liệu cho email
            $data = [
                'passenger_name' => $ticket->passenger_name,
                'pnr_code' => $booking->pnr_code,
                'ticket_code' => $ticket->ticket_code,
                'flight_number' => $flight->flight_number,
                'departure_time' => $flight->departure_time->format('d/m/Y H:i'),
                'seat_number' => $ticket->seat?->seat_number ?? 'N/A',
                'qr_code' => $boardingPass->qr_code_url,
            ];

            // 3. Gửi email
            Mail::send('emails.boarding-pass', $data, function ($message) use ($user, $booking) {
                $message
                    ->to($user->email)
                    ->subject("Thẻ Lên Máy Bay - SkyLink Airlines (PNR: {$booking->pnr_code})")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Email Boarding Pass gửi thành công", [
                'boarding_pass_id' => $this->boardingPassId,
                'user_email' => $user->email,
                'pnr_code' => $booking->pnr_code,
            ]);

        } catch (Exception $e) {
            Log::error("Lỗi khi gửi Boarding Pass Email", [
                'boarding_pass_id' => $this->boardingPassId,
                'error' => $e->getMessage(),
            ]);

            // Throw exception để queue có thể retry
            throw $e;
        }
    }

    /**
     * Job bị failed - ghi log lỗi
     * 
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error("SendBoardingPassEmailJob failed sau {$this->tries} lần retry", [
            'boarding_pass_id' => $this->boardingPassId,
            'error' => $exception->getMessage(),
        ]);
    }
}
