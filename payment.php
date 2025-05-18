<?php
session_start();
include 'includes/config.php';

// --- PHẦN 1: KIỂM TRA ĐĂNG NHẬP VÀ SESSION BOOKING DETAILS ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message_global'] = "Phiên làm việc hết hạn hoặc bạn chưa đăng nhập. Vui lòng đăng nhập lại.";
    if (isset($_SESSION['pending_booking_details'])) {
        $_SESSION['redirect_target_after_login_for_payment'] = $_SESSION['pending_booking_details'];
    }
    header('Location: loginregister.php?tab=login&return_to=payment');
    exit();
}

if (isset($_SESSION['redirect_target_after_login_for_payment']) && !isset($_SESSION['pending_booking_details'])) {
    $_SESSION['pending_booking_details'] = $_SESSION['redirect_target_after_login_for_payment'];
    unset($_SESSION['redirect_target_after_login_for_payment']);
}

if (!isset($_SESSION['pending_booking_details'])) {
    $_SESSION['error_message_global'] = "Không có thông tin đặt phòng để thanh toán. Vui lòng bắt đầu lại.";
    header('Location: index.php');
    exit();
}

$booking = $_SESSION['pending_booking_details'];
$userId = $_SESSION['user_id'];

// --- PHẦN 2: XỬ LÝ SUBMIT FORM THANH TOÁN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment_action'])) {
    $chosen_payment_method = $booking['chosen_payment_method'];
    $payment_inputs_valid = true;

    // Mô phỏng kiểm tra thông tin thẻ Visa (nếu là Visa)
    if ($chosen_payment_method === 'visa') {
        $card_number = trim($_POST['card_number_visa'] ?? '');
        $card_expiry = trim($_POST['card_expiry_visa'] ?? '');
        $card_cvc = trim($_POST['card_cvc_visa'] ?? '');
        $card_holder_name = trim($_POST['card_holder_name_visa'] ?? '');

        unset($_SESSION['payment_form_error_visa']); // Xóa lỗi cũ
        $visa_errors = [];

        if (empty($card_number) || !preg_match('/^(\d{4} ?){3}\d{4}$/', str_replace(' ', '', $card_number))) {
            $visa_errors[] = "Số thẻ Visa không hợp lệ (cần 16 chữ số).";
        }
        if (empty($card_expiry) || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $card_expiry)) {
            $visa_errors[] = "Ngày hết hạn (MM/YY) không hợp lệ.";
        }
        if (empty($card_cvc) || !preg_match('/^\d{3,4}$/', $card_cvc)) {
            $visa_errors[] = "CVC/CVV không hợp lệ (3 hoặc 4 chữ số).";
        }
        if (empty($card_holder_name)) {
            $visa_errors[] = "Tên chủ thẻ không được trống.";
        }

        if (!empty($visa_errors)) {
            $_SESSION['payment_form_error_visa'] = implode("<br>", $visa_errors);
            $payment_inputs_valid = false;
        }
    }

    if ($payment_inputs_valid) {
        $payment_successful = false;
        $simulated_transaction_id = strtoupper($chosen_payment_method) . '-TRX-' . time() . '-' . rand(1000,9999);

        if ($chosen_payment_method === 'cod') {
            $payment_successful = true;
            $simulated_transaction_id = 'COD-PENDING-' . time() . '-' . rand(1000,9999);
        } else {
            if (rand(1, 100) <= 55) { // 70% tỷ lệ thành công
                $payment_successful = true;
                $simulated_transaction_id = strtoupper($chosen_payment_method) . '-SUCCESS-' . time() . '-' . rand(1000,9999);
            } else {
                $payment_successful = false;
                $simulated_transaction_id = strtoupper($chosen_payment_method) . '-FAILED-' . time() . '-' . rand(1000,9999);
            }
        }

        if ($payment_successful) {
            try {
                $pdo->beginTransaction();
                $stmtBooking = $pdo->prepare("INSERT INTO bookings
                    (user_id, hotel_id, checkin_date, checkout_date, total_price,
                     customer_name, customer_email, customer_phone, special_requests,
                     transaction_id, payment_method, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmtBooking->execute([
                    $userId, $booking['hotel_id'], $booking['checkin_date'], $booking['checkout_date'],
                    $booking['total_price'], $booking['customer_name'], $booking['customer_email'],
                    $booking['customer_phone'], $booking['special_requests'],
                    $simulated_transaction_id, $chosen_payment_method
                ]);
                $db_booking_id = $pdo->lastInsertId();

                $stmtBookingRoom = $pdo->prepare("INSERT INTO booking_rooms (booking_id, room_id, quantity) VALUES (?, ?, ?)");
                $stmtBookingRoom->execute([$db_booking_id, $booking['room_id'], $booking['quantity']]);

                $pdo->commit();

                $_SESSION['booking_user_email_for_confirmation'] = $booking['customer_email'];
                if ($chosen_payment_method === 'cod') {
                     $_SESSION['booking_confirmation_message'] = "Đặt phòng thành công với phương thức Thanh toán khi nhận phòng! Cảm ơn bạn đã ủng hộ, vui lòng kiểm tra lại gmail (" . htmlspecialchars($booking['customer_email']) . ") để xác nhận lại thông tin một lần nữa. Nhân viên sẽ liên hệ với bạn sớm.";
                } else {
                     $_SESSION['booking_confirmation_message'] = "Thanh toán thành công! Cảm ơn bạn đã ủng hộ, vui lòng kiểm tra lại gmail (" . htmlspecialchars($booking['customer_email']) . ") để xác nhận lại thông tin một lần nữa.";
                }
                $_SESSION['last_booking_id_info'] = $db_booking_id;
                $_SESSION['last_transaction_id_info'] = $simulated_transaction_id;
                $_SESSION['last_payment_method_info'] = $chosen_payment_method;
                unset($_SESSION['pending_booking_details']);

                header('Location: booking_success.php'); // CHUYỂN HƯỚNG ĐẾN TRANG THÀNH CÔNG
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Lỗi lưu vào CSDL sau thanh toán: " . $e->getMessage());
                // Ngay cả khi thanh toán "thành công" nhưng CSDL lỗi, vẫn set lỗi và chuyển hướng
                $_SESSION['payment_error_message'] = "Lỗi hệ thống khi lưu đặt phòng. Mã lỗi: DB_SAVE_ERR. Vui lòng liên hệ hỗ trợ.";
                $_SESSION['failed_transaction_id_info'] = $simulated_transaction_id . "-DB_ERROR"; // Thêm ghi chú lỗi CSDL
                // Không xóa pending_booking_details để người dùng có thể thử lại nếu muốn
                header('Location: booking_success.php'); // CHUYỂN HƯỚNG ĐẾN TRANG THÀNH CÔNG/THẤT BẠI
                exit();
            }
        } else {
            // Xử lý khi thanh toán thất bại (do random 30% hoặc lỗi khác)
            // ĐẶT THÔNG BÁO THẤT BẠI MÀ BẠN MUỐN
            $_SESSION['payment_error_message'] = "Thanh toán thất bại, vui lòng thử lại hoặc thay đổi phương thức thanh toán.";
            $_SESSION['failed_transaction_id_info'] = $simulated_transaction_id;
            // Không xóa pending_booking_details để người dùng có thể thử lại
            header('Location: booking_success.php'); // CHUYỂN HƯỚNG ĐẾN TRANG THÀNH CÔNG/THẤT BẠI
            exit();
        }
    }
    // Nếu $payment_inputs_valid là false (lỗi nhập thẻ Visa), trang payment.php sẽ được render lại
    // và hiển thị $_SESSION['payment_form_error_visa'] trong phần HTML bên dưới.
}

// --- PHẦN 3: HIỂN THỊ HTML ---
// Phần HTML của payment.php giữ nguyên như phiên bản php_payment_page_v5_random_success
// (Nó sẽ hiển thị form cho Momo hoặc Visa dựa trên $booking['chosen_payment_method'] từ session)
$pageTitle = 'Xác nhận và Thanh toán';
include 'includes/header.php';
?>
<div class="payment-page-container">
    <div class="container content">
        <h2><i class="fas fa-lock"></i> Xác nhận và Thanh toán Đơn hàng</h2>

        <?php
        if (isset($_SESSION['error_message_global'])) {
            echo '<div class="message error-message main-error">' . $_SESSION['error_message_global'] . '</div>';
            unset($_SESSION['error_message_global']);
        }
        if (isset($_SESSION['payment_form_error_visa'])) { // Lỗi validate form Visa
            echo '<div class="message error-message form-validation-summary">' . $_SESSION['payment_form_error_visa'] . '</div>';
            unset($_SESSION['payment_form_error_visa']);
        }
        ?>

        <div class="booking-summary-payment-page">
            <h3><i class="fas fa-receipt"></i> Tóm tắt Đơn hàng</h3>
            <p><strong>Khách sạn:</strong> <?= htmlspecialchars($booking['hotel_name']) ?></p>
            <p><strong>Loại phòng:</strong> <?= htmlspecialchars($booking['room_type_name']) ?> (Số lượng: <?= $booking['quantity'] ?>)</p>
            <p><strong>Ngày nhận phòng:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($booking['checkin_date']))) ?></p>
            <p><strong>Ngày trả phòng:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($booking['checkout_date']))) ?></p>
            <p><strong>Số đêm:</strong> <?= $booking['nights'] ?></p>
            <p><strong>Tổng khách:</strong> <?= $booking['guests'] ?></p>
            <p><strong>Người đặt:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <h4 class="total-price-payment-page"><strong>Tổng thanh toán: <?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</strong></h4>
        </div>

        <div class="payment-interface-section">
            <h3><i class="fas fa-credit-card"></i> Thanh toán bằng: <?= strtoupper(htmlspecialchars($booking['chosen_payment_method'])) ?></h3>
            <form action="payment.php" method="POST" id="actualPaymentForm">
                <input type="hidden" name="confirm_payment_action" value="1">

                <?php if ($booking['chosen_payment_method'] === 'momo'): ?>
                    <div class="payment-momo-interface">
                        <p>Vui lòng quét mã QR sau bằng ứng dụng Momo để hoàn tất thanh toán (Mô phỏng).</p>
                        <img src="assets/images/sample_momo_qr.png" alt="Mã QR Momo (Mô phỏng)" class="momo-qr-image-payment">
                        <p class="payment-instruction-text">Sau khi thanh toán thành công trên Momo, hệ thống sẽ tự động xác nhận.</p>
                    </div>
                <?php elseif ($booking['chosen_payment_method'] === 'visa'): ?>
                    <div class="payment-visa-interface">
                        <p>Vui lòng nhập thông tin thẻ Visa của bạn (Mô phỏng - không lưu trữ).</p>
                        <div class="form-group-payment">
                            <label for="card_number_visa">Số thẻ:</label>
                            <input type="text" id="card_number_visa" name="card_number_visa" class="form-control-payment" placeholder="XXXX XXXX XXXX XXXX" value="<?= htmlspecialchars($_POST['card_number_visa'] ?? '') ?>" required>
                        </div>
                        <div class="form-row-payment">
                            <div class="form-group-payment col-half-payment">
                                <label for="card_expiry_visa">Ngày hết hạn (MM/YY):</label>
                                <input type="text" id="card_expiry_visa" name="card_expiry_visa" class="form-control-payment" placeholder="MM/YY" value="<?= htmlspecialchars($_POST['card_expiry_visa'] ?? '') ?>" required>
                            </div>
                            <div class="form-group-payment col-half-payment">
                                <label for="card_cvc_visa">CVC/CVV:</label>
                                <input type="text" id="card_cvc_visa" name="card_cvc_visa" class="form-control-payment" placeholder="123" value="<?= htmlspecialchars($_POST['card_cvc_visa'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-group-payment">
                            <label for="card_holder_name_visa">Tên chủ thẻ:</label>
                            <input type="text" id="card_holder_name_visa" name="card_holder_name_visa" class="form-control-payment" placeholder="NGUYEN VAN A" value="<?= htmlspecialchars($_POST['card_holder_name_visa'] ?? '') ?>" required>
                        </div>
                    </div>
                <?php elseif ($booking['chosen_payment_method'] === 'cod'): ?>
                     <div class="payment-cod-interface">
                        <p><i class="fas fa-shipping-fast"></i> Bạn đã chọn thanh toán khi nhận phòng.</p>
                        <p>Vui lòng chuẩn bị số tiền <?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ để thanh toán cho nhân viên khách sạn khi làm thủ tục check-in.</p>
                    </div>
                <?php else: ?>
                    <p class="error-text-payment">Phương thức thanh toán không hợp lệ đã được chọn.</p>
                <?php endif; ?>

                <?php if (in_array($booking['chosen_payment_method'], ['momo', 'visa', 'cod'])): ?>
                <button type="submit" class="btn btn-success btn-lg btn-block btn-confirm-payment-final">
                    <i class="fas fa-check-circle"></i>
                    <?= ($booking['chosen_payment_method'] === 'cod') ? 'Hoàn tất đặt phòng (COD)' : 'Xác nhận Thanh toán & Đặt phòng' ?>
                </button>
                <?php endif; ?>
            </form>
             <div class="payment-note-secure">
                <p><i class="fas fa-shield-alt"></i> Giao dịch của bạn được bảo mật (Đây là môi trường mô phỏng).</p>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
