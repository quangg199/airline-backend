<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Xác nhận thanh toán sử dụng Adapter Pattern
     * POST /api/bookings/pay
     */
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_ids'   => ['required', 'array'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
            'payment_method'=> ['required', 'string', 'in:momo,vnpay'],
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

            $totalAmount = 0;
            $orderId = '';
            foreach ($bookings as $booking) {
                if ($booking->status !== 'pending' || ($booking->expires_at && $booking->expires_at <= now())) {
                    if ($booking->status === 'pending') {
                        $booking->update(['status' => 'cancelled']);
                    }
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Đơn đặt chỗ {$booking->pnr_code} đã hết hạn hoặc không ở trạng thái chờ thanh toán.",
                    ], 400);
                }
                $totalAmount += $booking->total_amount;
                $orderId .= $booking->pnr_code . '_';
            }
            $orderId = trim($orderId, '_');

            // Áp dụng Factory & Adapter Pattern
            try {
                $gateway = PaymentFactory::create($validated['payment_method']);
                $paymentResult = $gateway->processPayment($totalAmount, $orderId);
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lỗi cổng thanh toán: ' . $e->getMessage(),
                ], 500);
            }

            if ($paymentResult['status'] === 'success') {
                foreach ($bookings as $booking) {
                    $booking->status = 'paid';
                    $booking->save();

                    // Lưu lịch sử giao dịch vào bảng payments
                    // Vì 1 phiên thanh toán có thể trả cho 2 vé (khứ hồi), ta thêm ID vé vào để tránh lỗi unique
                    Payment::create([
                        'booking_id'     => $booking->id,
                        'transaction_id' => $paymentResult['transaction_id'] . '_B' . $booking->id,
                        'amount'         => $booking->total_amount,
                        'payment_method' => $validated['payment_method'],
                        'status'         => 'success',
                        'paid_at'        => now(),
                    ]);
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => $paymentResult['message'],
                    'data'    => $paymentResult
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Thanh toán thất bại.',
            ], 400);
        });
    }
}
