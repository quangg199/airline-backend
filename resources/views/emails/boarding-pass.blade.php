<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thẻ Lên Máy Bay - SkyLink Airlines</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }
        .boarding-pass-info {
            background-color: #f9f9f9;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .qr-code {
            text-align: center;
            margin: 30px 0;
        }
        .qr-code img {
            max-width: 250px;
            height: auto;
        }
        .important-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .safety-commitment {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>✈️ SkyLink Airlines</h1>
            <p>Thẻ Lên Máy Bay Điện Tử</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                <p>Xin chào <strong>{{ $passenger_name }}</strong>,</p>
                <p>Chúng tôi xác nhận rằng bạn đã Check-in thành công cho chuyến bay của mình. Vui lòng chuẩn bị các giấy tờ cần thiết và đến cổng lên máy bay đúng giờ.</p>
            </div>

            <!-- Boarding Pass Info -->
            <div class="boarding-pass-info">
                <div class="info-row">
                    <span class="info-label">Mã Đặt Chỗ (PNR):</span>
                    <span class="info-value">{{ $pnr_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mã Vé:</span>
                    <span class="info-value">{{ $ticket_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Chuyến Bay:</span>
                    <span class="info-value">{{ $flight_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thời Gian Cất Cánh:</span>
                    <span class="info-value">{{ $departure_time }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ghế Ngồi:</span>
                    <span class="info-value">{{ $seat_number }}</span>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-code">
                <p style="color: #666; font-size: 13px; margin-top: 0;">Mã QR - Quét tại cổng lên máy bay</p>
                <img src="{{ $qr_code }}" alt="QR Code">
            </div>

            <!-- Important Notice -->
            <div class="important-notice">
                <strong>⚠️ Lưu Ý Quan Trọng:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Vui lòng đến sân bay <strong>ít nhất 2 giờ</strong> trước thời gian cất cánh (chuyến bay nước ngoài)</li>
                    <li>Mang theo hộ chiếu hoặc giấy tờ tùy thân hợp lệ</li>
                    <li>Kiểm tra lại hành lý theo quy định hãng hàng không</li>
                </ul>
            </div>

            <!-- Safety Commitment -->
            <div class="safety-commitment">
                <strong>🛡️ Cam Kết An Toàn Bay:</strong>
                <p style="margin: 10px 0;">
                    Bạn đồng ý không mang theo những vật phẩm cấm như chất nổ, pin dự phòng có công suất cao, hoặc các vật phẩm nguy hiểm khác. 
                    SkyLink Airlines cam kết đảm bảo sự an toàn cho tất cả hành khách.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2026 SkyLink Airlines. Tất cả quyền được bảo lưu.</p>
            <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
            <p>Nếu có câu hỏi, vui lòng liên hệ với <a href="mailto:support@skylink.com" style="color: #667eea;">support@skylink.com</a></p>
        </div>
    </div>
</body>
</html>
