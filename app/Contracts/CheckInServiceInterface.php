<?php

namespace App\Contracts;

/**
 * CheckInServiceInterface
 * 
 * Định nghĩa hợp đồng (contract) cho Check-in Service.
 * Giúp áp dụng Dependency Inversion Principle (DIP).
 */
interface CheckInServiceInterface
{
    /**
     * Thực hiện Check-in cho hành khách
     * 
     * @param string $pnrCode - Mã đặt chỗ (6 ký tự)
     * @param string $passengerName - Tên hành khách
     * @return array - Dữ liệu Boarding Pass
     * @throws \Exception - Nếu có lỗi trong quá trình Check-in
     */
    public function checkIn(string $pnrCode, string $passengerName): array;

    /**
     * Kiểm tra xem hành khách đã Check-in chưa
     * 
     * @param string $pnrCode
     * @param string $passengerName
     * @return bool
     */
    public function hasCheckedIn(string $pnrCode, string $passengerName): bool;
}
