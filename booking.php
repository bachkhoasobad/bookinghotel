<?php
session_start();
include 'includes/config.php';
$pageTitle = 'Đặt phòng';


// Kiểm tra xem các tham số cần thiết có tồn tại không
if (!isset($_GET['hotel_id']) || !is_numeric($_GET['hotel_id']) ||
    !isset($_GET['room_type']) || !is_numeric($_GET['room_type']) ||
    !isset($_GET['checkin_date']) || empty($_GET['checkin_date']) ||
    !isset($_GET['checkout_date']) || empty($_GET['checkout_date']) ||
    !isset($_GET['guests']) || !is_numeric($_GET['guests']) || $_GET['guests'] < 1 ||
    !isset($_GET['quantity']) || !is_numeric($_GET['quantity']) || $_GET['quantity'] < 1) {
    header('Location: index.php'); // Chuyển hướng nếu thiếu tham số
    exit();
}

$hotelId = intval($_GET['hotel_id']);
$roomId = intval($_GET['room_type']);
$checkinDate = htmlspecialchars($_GET['checkin_date']);
$checkoutDate = htmlspecialchars($_GET['checkout_date']);
$guests = intval($_GET['guests']);
$quantity = intval($_GET['quantity']);

try {
    // Lấy thông tin khách sạn
    $stmtHotel = $pdo->prepare("SELECT name, image, location, description FROM hotels WHERE id = ?");
    $stmtHotel->execute([$hotelId]);
    $hotel = $stmtHotel->fetch(PDO::FETCH_ASSOC);

    // Lấy thông tin phòng
    $stmtRoom = $pdo->prepare("SELECT room_type, price, capacity FROM rooms WHERE id = ? AND hotel_id = ?");
    $stmtRoom->execute([$roomId, $hotelId]);
    $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);

    if (!$hotel || !$room) {
        die("<div class='error'>Lỗi: Không tìm thấy thông tin khách sạn hoặc phòng.</div>");
    }

    // Kiểm tra số lượng khách có phù hợp với sức chứa của phòng không
    if ($guests > $room['capacity']) {
        die("<div class='error'>Lỗi: Số lượng khách vượt quá sức chứa của phòng.</div>");
    }

    // Tính tổng giá (chưa tính số đêm, sẽ được tính khi đặt phòng)
    $roomPrice = $room['price'];

    // Xử lý khi form đặt phòng được gửi
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $customerName = htmlspecialchars($_POST['name'] ?? '');
        $customerEmail = htmlspecialchars($_POST['email'] ?? '');
        $customerPhone = htmlspecialchars($_POST['phone'] ?? '');
        $specialRequests = htmlspecialchars($_POST['special_requests'] ?? '');
        $paymentMethod = htmlspecialchars($_POST['payment_method'] ?? '');

        if (empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($paymentMethod)) {
            $error = "Vui lòng nhập đầy đủ thông tin liên hệ và chọn phương thức thanh toán.";
        } else {
            // Tính tổng giá dựa trên số đêm thực tế
            $startDate = new DateTime($checkinDate);
            $endDate = new DateTime($checkoutDate);
            $interval = $startDate->diff($endDate);
            $nights = $interval->days;
            $totalPrice = $roomPrice * $nights * $quantity;

            // Lưu thông tin đặt phòng vào session để chuyển sang trang thanh toán
            $_SESSION['booking_data'] = [
                'hotel_id' => $hotelId,
                'room_id' => $roomId,
                'checkin_date' => $checkinDate,
                'checkout_date' => $checkoutDate,
                'guests' => $guests,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'special_requests' => $specialRequests,
            ];
            $_SESSION['payment_method'] = $paymentMethod; // Lưu phương thức thanh toán vào session

            // Chuyển hướng đến trang thanh toán
            header('Location: payment.php');
            exit();
        }
    }

} catch (PDOException $e) {
    die("<div class='error'>Lỗi tải dữ liệu: " . $e->getMessage() . "</div>");
}

// Lấy thông tin người dùng từ session nếu đã đăng nhập
$loggedInName = htmlspecialchars($_SESSION['username'] ?? '');
$loggedInEmail = htmlspecialchars($_SESSION['verify_email'] ?? '');

include 'includes/header.php';
?>

<div class="booking-page">
    <div class="container">
        <h1>Đặt phòng tại <?= htmlspecialchars($hotel['name'] ?? 'Không xác định') ?></h1>
        <div class="hotel-details">
            <?php
            $imagePath = "assets/images/" . ($hotel['image'] ?? 'default.jpg');
            if (file_exists($imagePath)): ?>
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($hotel['name'] ?? 'Không xác định') ?>">
            <?php else: ?>
                <img src="assets/images/default.jpg" alt="<?= htmlspecialchars($hotel['name'] ?? 'Không xác định') ?>">
            <?php endif; ?>
            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($hotel['location'] ?? 'Không có địa điểm') ?></p>
            <p><?= htmlspecialchars($hotel['description'] ?? 'Không có mô tả') ?></p>
        </div>

        <h2>Thông tin đặt phòng</h2>
        <form action="payment.php" method="POST" id="bookingForm">
            <input type="hidden" name="hotel_id" value="<?= $hotelId ?>">
            <input type="hidden" name="room_id" value="<?= $roomId ?>">
            <div class="form-group">
                <label>Loại phòng:</label>
                <input type="text" value="<?= htmlspecialchars($room['room_type'] ?? 'Không xác định') ?>" readonly>
            </div>
            <div class="form-group">
                <label>Số lượng phòng:</label>
                <input type="number" name="quantity" value="<?= $quantity ?>" min="1" required>
            </div>
            <div class="form-group">
                <label for="checkin_date">Ngày nhận phòng:</label>
                <input type="date" id="checkin_date" name="checkin_date" value="<?= htmlspecialchars($checkinDate) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="checkout_date">Ngày trả phòng:</label>
                <input type="date" id="checkout_date" name="checkout_date" value="<?= htmlspecialchars($checkoutDate) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="guests">Số lượng khách:</label>
                <input type="text" value="<?= htmlspecialchars($guests) ?>" readonly>
                <input type="hidden" name="guests" value="<?= $guests ?>">
            </div>
            <div class="form-group">
                <label for="name">Họ và tên:</label>
                <input type="text" id="name" name="name" value="<?= $loggedInName ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= $loggedInEmail ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="special_requests">Yêu cầu đặc biệt (nếu có):</label>
                <textarea id="special_requests" name="special_requests"></textarea>
            </div>

            <h2>Chọn phương thức thanh toán</h2>
            <div class="payment-options">
                <div class="form-group">
                    <input type="radio" id="payment_method_momo" name="payment_method" value="momo" required>
                    <label for="payment_method_momo"><i class="fas fa-mobile-alt"></i> Momo</label>
                </div>
                <div class="form-group">
                    <input type="radio" id="payment_method_credit_card" name="payment_method" value="credit_card" required>
                    <label for="payment_method_credit_card"><i class="far fa-credit-card"></i> Thẻ tín dụng</label>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <button type="submit" class="book-now-btn">Tiến hành thanh toán</button>
            <div id="booking-message"></div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>