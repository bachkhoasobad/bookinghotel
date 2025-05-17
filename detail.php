<?php
session_start();
include 'includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$hotelId = intval($_GET['id']);
$checkinDate = htmlspecialchars($_GET['checkin_date'] ?? date('Y-m-d'));
$checkoutDate = htmlspecialchars($_GET['checkout_date'] ?? date('Y-m-d', strtotime('+1 day')));
$guests = isset($_GET['guests']) ? max(1, (int)$_GET['guests']) : 1;

try {
    // Lấy thông tin khách sạn
    $stmtHotel = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmtHotel->execute([$hotelId]);
    $hotel = $stmtHotel->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        header('Location: index.php');
        exit();
    }

    // Lấy các loại phòng có sẵn cho khách sạn và phù hợp với số lượng khách
    $sqlRooms = "SELECT r.*
                 FROM rooms r
                 WHERE r.hotel_id = ?
                 AND r.capacity >= ?
                 ORDER BY r.price ASC"; // Sắp xếp theo giá để dễ lựa chọn

    $stmtRooms = $pdo->prepare($sqlRooms);
    $stmtRooms->execute([$hotelId, $guests]);
    $rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Lỗi tải dữ liệu trang chi tiết: " . $e->getMessage());
    die("<div class='error'>Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.</div>");
}

$pageTitle = htmlspecialchars($hotel['name']);
include 'includes/header.php';
?>

<div class="detail-page">
    <div class="container">
        <h1><?= htmlspecialchars($hotel['name']) ?></h1>
        <div class="hotel-details">
            <?php
            $imagePath = "assets/images/" . $hotel['image'];
            if (file_exists($imagePath)): ?>
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($hotel['name']) ?>">
            <?php else: ?>
                <img src="assets/images/default.jpg" alt="<?= htmlspecialchars($hotel['name']) ?>">
            <?php endif; ?>
            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($hotel['location'] ?? 'Không có địa điểm') ?></p>
            <p><?= htmlspecialchars($hotel['description'] ?? 'Không có mô tả') ?></p>
            <p>Đánh giá: <?= number_format($hotel['rating'] ?? 0, 1) ?> <i class="fas fa-star"></i></p>
            <?php if (!empty($hotel['amenities'])): ?>
                <h2>Tiện nghi</h2>
                <ul>
                    <?php
                    // Giả sử $hotel['amenities'] là một chuỗi JSON
                    $amenitiesArray = json_decode($hotel['amenities'], true);
                    if (is_array($amenitiesArray)):
                        foreach ($amenitiesArray as $amenity): ?>
                            <li><?= htmlspecialchars($amenity) ?></li>
                        <?php endforeach;
                    else: ?>
                        <li><?= htmlspecialchars($hotel['amenities']) ?></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($hotel['policies'])): ?>
                <h2>Chính sách</h2>
                <p><?= nl2br(htmlspecialchars(str_replace(['{"checkin_time":', '"checkout_time":', '"}', '":"'], ['', '', '', ': '], $hotel['policies']))) ?></p>
                <?php endif; ?>
        </div>

        <div class="room-details">
            <h2>Các loại phòng</h2>
            <?php if (empty($rooms)): ?>
                <p>Không có phòng nào phù hợp với số lượng khách (<?= htmlspecialchars($guests) ?>) vào thời điểm này.</p>
                <p>Ngày nhận phòng dự kiến: <?= date('d/m/Y', strtotime($checkinDate)) ?></p>
                <p>Ngày trả phòng dự kiến: <?= date('d/m/Y', strtotime($checkoutDate)) ?></p>
            <?php else: ?>
                <form action="booking.php" method="GET">
                    <input type="hidden" name="hotel_id" value="<?= urlencode($hotelId) ?>">
                    <input type="hidden" name="checkin_date" value="<?= urlencode($checkinDate) ?>">
                    <input type="hidden" name="checkout_date" value="<?= urlencode($checkoutDate) ?>">
                    <input type="hidden" name="guests" value="<?= urlencode($guests) ?>">
                    <div class="form-group">
                        <label for="room_type">Chọn loại phòng:</label>
                        <select id="room_type" name="room_type" required>
                            <option value="">-- Chọn loại phòng --</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= urlencode($room['id']) ?>">
                                    <?= htmlspecialchars($room['room_type']) ?> (<?= htmlspecialchars($room['capacity']) ?> người, <?= number_format($room['price'], 0, ',', '.') ?> VNĐ/đêm)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Số lượng phòng:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" required>
                    </div>
                    <p>Ngày nhận phòng dự kiến: <?= date('d/m/Y', strtotime($checkinDate)) ?></p>
                    <p>Ngày trả phòng dự kiến: <?= date('d/m/Y', strtotime($checkoutDate)) ?></p>
                    <button type="submit" class="book-btn">Đặt phòng</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>