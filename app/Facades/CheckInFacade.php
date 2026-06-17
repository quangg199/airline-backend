<?php

namespace App\Facades;

use App\Services\CheckInService;
use Illuminate\Support\Facades\Facade;

/**
 * CheckInFacade
 * 
 * Facade cung cấp giao diện đơn giản để truy cập CheckInService.
 * Giúp code ở Controller sạch sẽ hơn và dễ test hơn.
 * 
 * Sử dụng:
 *   $result = CheckInFacade::execute($pnrCode, $passengerName);
 * 
 * @method static array execute(string $pnrCode, string $passengerName)
 * @method static bool hasCheckedIn(string $pnrCode, string $passengerName)
 */
class CheckInFacade extends Facade
{
    /**
     * Lấy tên của component được facade hóa
     * 
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'check-in-service';
    }

    /**
     * Wrapper method - Thực hiện Check-in
     * 
     * @param string $pnrCode
     * @param string $passengerName
     * @return array
     */
    public static function execute(string $pnrCode, string $passengerName): array
    {
        return app('check-in-service')->checkIn($pnrCode, $passengerName);
    }

    /**
     * Wrapper method - Kiểm tra đã Check-in chưa
     * 
     * @param string $pnrCode
     * @param string $passengerName
     * @return bool
     */
    public static function hasCheckedIn(string $pnrCode, string $passengerName): bool
    {
        return app('check-in-service')->hasCheckedIn($pnrCode, $passengerName);
    }
}
