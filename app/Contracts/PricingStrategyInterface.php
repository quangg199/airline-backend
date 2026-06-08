<?php

namespace App\Contracts;

/**
 * PricingStrategyInterface (Strategy Pattern — Behavioral)
 *
 * Định nghĩa hợp đồng (contract) cho các thuật toán tính giá có thể hoán đổi.
 * Mỗi concrete strategy đóng gói một quy tắc tính giá riêng biệt
 * (VD: một chiều giữ nguyên giá, khứ hồi giảm 10%) mà caller không cần
 * biết logic tính toán bên trong.
 *
 * SOLID Compliance:
 * - ISP : Interface gọn nhẹ, chỉ chứa method thiết yếu duy nhất.
 * - OCP : Thêm pricing tier mới (student, VIP, seasonal) chỉ cần tạo class
 *         mới implement interface này — KHÔNG sửa code cũ.
 * - LSP : Mọi implementation đều thay thế được cho nhau mà không phá vỡ behavior.
 * - DIP : High-level module (FlightSearchProxy) phụ thuộc vào abstraction này,
 *         không phụ thuộc vào concrete strategy.
 */
interface PricingStrategyInterface
{
    /**
     * Tính giá hiển thị từ giá gốc (base_price) của chuyến bay.
     *
     * @param  float  $basePrice  Giá gốc lưu trong DB (đơn vị: VND).
     * @return float  Giá sau khi áp dụng quy tắc của strategy (đơn vị: VND).
     */
    public function calculate(float $basePrice): float;
}
