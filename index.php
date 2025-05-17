<?php
session_start();
include 'includes/config.php';
$pageTitle = 'Trang chủ';
include 'includes/header.php';

// Lấy 3 khách sạn nổi bật
try {
    $stmt = $pdo->query("
        SELECT h.*
        FROM hotels h
        ORDER BY h.rating DESC
        LIMIT 3
    ");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Lỗi tải dữ liệu trang chủ: " . $e->getMessage());
    die("<div class='error'>Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.</div>");
}

// Xử lý giá trị mặc định cho ngày
$defaultCheckin = date('Y-m-d');
$defaultCheckout = date('Y-m-d', strtotime('+1 day'));
?>

<div class="hero">
    <div class="search-container">
        <h1>Khám phá những điểm đến tuyệt vời</h1>
        <form class="search-form" method="GET" action="search.php">
            <div class="form-row">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="destination" name="destination" placeholder="Bạn muốn đi đâu?" required value="<?= htmlspecialchars($_GET['destination'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" id="checkin" name="checkin" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_GET['checkin'] ?? $defaultCheckin) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" id="checkout" name="checkout" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($_GET['checkout'] ?? $defaultCheckout) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="number" id="guests" name="guests" min="1" placeholder="Số lượng người lớn" required value="<?= htmlspecialchars($_GET['guests'] ?? 1) ?>">
                    </div>
                </div>
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<section class="featured-hotels">
    <h2>Khách sạn nổi bật</h2>
    <div class="hotel-grid">
        <?php if(count($hotels) > 0): ?>
            <?php foreach($hotels as $hotel):
                $imagePath = "assets/images/" . $hotel['image'];
                if (file_exists($imagePath)): ?>
                    <div class="hotel-card">
                        <div class="hotel-image">
                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                 alt="<?= htmlspecialchars($hotel['name']) ?>">
                            <div class="hotel-rating">
                                <i class="fas fa-star"></i>
                                <?= number_format($hotel['rating'], 1) ?>
                            </div>
                        </div>
                        <div class="hotel-info">
                            <h3>
                                <a href="detail.php?id=<?= urlencode($hotel['id']) ?>">
                                    <?= htmlspecialchars($hotel['name']) ?>
                                </a>
                            </h3>
                            <div class="hotel-meta">
                                <span class="location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($hotel['location']) ?>
                                </span>
                            </div>
                            <p class="hotel-description">
                                <?= htmlspecialchars(substr($hotel['description'], 0, 100)) ?>...
                            </p>
                            <div class="hotel-price">
                                Từ <?= number_format($hotel['price'], 0, ',', '.') ?>đ/đêm
                            </div>
                            <a href="detail.php?id=<?= urlencode($hotel['id']) ?>" class="book-btn">Xem chi tiết</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hotel-card">
                        <div class="hotel-image">
                            <img src="assets/images/default.jpg" alt="<?= htmlspecialchars($hotel['name']) ?>">
                            <div class="hotel-rating">
                                <i class="fas fa-star"></i>
                                <?= number_format($hotel['rating'], 1) ?>
                            </div>
                        </div>
                        <div class="hotel-info">
                            <h3>
                                <a href="detail.php?id=<?= urlencode($hotel['id']) ?>">
                                    <?= htmlspecialchars($hotel['name']) ?>
                                </a>
                            </h3>
                            <div class="hotel-meta">
                                <span class="location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($hotel['location']) ?>
                                </span>
                            </div>
                            <p class="hotel-description">
                                <?= htmlspecialchars(substr($hotel['description'], 0, 100)) ?>...
                            </p>
                            <div class="hotel-price">
                                Từ <?= number_format($hotel['price'], 0, ',', '.') ?>đ/đêm
                            </div>
                            <a href="detail.php?id=<?= urlencode($hotel['id']) ?>" class="book-btn">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">Không tìm thấy khách sạn nổi bật</div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>