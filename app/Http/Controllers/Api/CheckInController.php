<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Facades\CheckInFacade;
use App\Jobs\SendBoardingPassEmailJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CheckInController
 * 
 * Xử lý các request liên quan đến Check-in
 * Endpoint công khai (TIER 1) - không yêu cầu xác thực
 */
class CheckInController extends Controller
{
    /**
     * Thực hiện Check-in cho hành khách
     * 
     * POST /api/check-in
     * 
     * Request body:
     * {
     *   "pnr_code": "ABC123",
     *   "passenger_name": "Nguyễn Văn A"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processCheckIn(Request $request): JsonResponse
    {
        try {
            // 1. Validate Input (PNR code, tên hành khách)
            $validated = $request->validate([
                'pnr_code' => 'required|string|size:6',
                'passenger_name' => 'required|string|max:255',
            ]);

            // 2. Gọi CheckInFacade để xử lý Check-in
            $boardingPass = CheckInFacade::execute(
                $validated['pnr_code'],
                $validated['passenger_name']
            );

            // 3. Dispatch job để gửi email Boarding Pass (background job)
            SendBoardingPassEmailJob::dispatch($boardingPass['boarding_pass_id']);

            // 4. Trả về kết quả Check-in thành công
            return response()->json([
                'status' => 'success',
                'message' => 'Check-in thành công! Thẻ lên máy bay đã gửi đến email của bạn.',
                'data' => $boardingPass,
            ], 200);

        } catch (\Exception $e) {
            // Trả về lỗi
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Truy vấn thông tin Boarding Pass đã được Check-in
     * 
     * GET /api/check-in?pnr_code=ABC123&passenger_name=Nguyễn Văn A
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pnr_code' => 'required|string|size:6',
                'passenger_name' => 'required|string|max:255',
            ]);

            $hasCheckedIn = CheckInFacade::hasCheckedIn(
                $validated['pnr_code'],
                $validated['passenger_name']
            );

            return response()->json([
                'status' => 'success',
                'has_checked_in' => $hasCheckedIn,
                'message' => $hasCheckedIn
                    ? 'Hành khách đã Check-in.'
                    : 'Hành khách chưa Check-in.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
