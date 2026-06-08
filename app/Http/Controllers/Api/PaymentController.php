<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Xác nhận thanh toán (Mock)
     * POST /api/bookings/pay
     *
     * Nhận vào mảng booking_ids (hoặc 1 booking_id).
     * Kiểm tra trạng thái phải là 'pending'.
     * Nếu OK, cập nhật trạng thái thành 'paid'.
     */
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_ids'   => ['required', 'array'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
            'payment_method'=> ['required', 'string', 'in:momo,visa'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $bookings = Booking::whereIn('id', $validated['booking_ids'])
                               ->where('user_id', $request->user()->id)
                               ->lockForUpdate() // Chống race condition thanh toán 2 lần
                               ->get();

            if ($bookings->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không tìm thấy đơn hàng.',
                ], 404);
            }

            foreach ($bookings as $booking) {
                if ($booking->status !== 'pending') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Đơn hàng {$booking->pnr_code} không ở trạng thái chờ thanh toán.",
                    ], 400);
                }

                $booking->status = 'paid';
                $booking->save();

                // Lưu mock record vào bảng payments nếu có bảng đó
                // $booking->payment()->create([ 'method' => $validated['payment_method'], 'amount' => $booking->total_amount ]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Thanh toán thành công!',
            ]);
        });
    }
}
