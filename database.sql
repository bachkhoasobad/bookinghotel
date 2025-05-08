-- Bảng users
CREATE TABLE `users` (
  `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL UNIQUE,
  `password` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `verify_otp` varchar(6) DEFAULT NULL,
  `verify_otp_expire` int(11) DEFAULT NULL
);
-- Bảng hotels
CREATE TABLE `hotels` (
  `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `price` decimal(10, 0) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2, 1) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `policies` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Bảng rooms
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_type VARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    capacity INT NOT NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id)
);

-- Bảng bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng booking_rooms
CREATE TABLE booking_rooms (
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    PRIMARY KEY (booking_id, room_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);