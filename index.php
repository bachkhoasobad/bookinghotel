<?php
session_start();
include 'includes/config.php';
$pageTitle = 'Trang chủ';
include 'includes/header.php';

// Lấy 3 khách sạn nổi bật
try {
    $stmt = $pdo->query("
        SELECT *
        FROM hotels
        ORDER BY rating DESC, price ASC
        LIMIT 3
    ");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("<div class='error'>Lỗi tải dữ liệu: " . $e->getMessage() . "</div>");
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
                        <input type="text" 
                               id="destination" 
                               name="destination" 
                               placeholder="Bạn muốn đi đâu?" 
                               required
                               value="<?= htmlspecialchars($_GET['destination'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" 
                               id="checkin" 
                               name="checkin" 
                               required
                               min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_GET['checkin'] ?? $defaultCheckin) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" 
                               id="checkout" 
                               name="checkout" 
                               required
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               value="<?= htmlspecialchars($_GET['checkout'] ?? $defaultCheckout) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <select id="guests" name="guests">
                            <option value="1" <?= ($_GET['guests'] ?? 1) == 1 ? 'selected' : '' ?>>1 người lớn</option>
                            <option value="2" <?= ($_GET['guests'] ?? 1) == 2 ? 'selected' : '' ?>>2 người lớn</option>
                            <option value="3" <?= ($_GET['guests'] ?? 1) == 3 ? 'selected' : '' ?>>3 người lớn</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<section class="featured-hotels">
    <h2>Khách sạn nổi bật</h2>
    <div class="hotel-grid">
        <?php if(count($hotels) > 0): ?>
            <?php foreach($hotels as $hotel): ?>
                <div class="hotel-card">
                    <div class="hotel-image">
                        <img src="assets/images/<?php echo htmlspecialchars($hotel['image']); ?>"
                             alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                        <div class="hotel-rating">
                            <i class="fas fa-star"></i>
                            <?php echo number_format($hotel['rating'], 1); ?>
                        </div>
                    </div>
                    <div class="hotel-info">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <div class="hotel-meta">
                            <span class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($hotel['location']); ?>
                            </span>
                        </div>
                        <p class="hotel-description">
                            <?php echo htmlspecialchars($hotel['description']); ?>
                        </p>
                        <div class="hotel-price">
                            <?php echo number_format($hotel['price'], 0, ',', '.'); ?>đ/đêm
                        </div>
                        <a href="detail.php?id=<?= $hotel['id'] ?>" class="book-btn">Xem chi tiết</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">Không tìm thấy khách sạn nào</div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>