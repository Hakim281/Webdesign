CREATE DATABASE IF NOT EXISTS real_estate_portal
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE real_estate_portal;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS properties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    location VARCHAR(120) NOT NULL,
    price_ksh INT UNSIGNED NOT NULL,
    bedrooms TINYINT UNSIGNED NOT NULL,
    bathrooms TINYINT UNSIGNED NOT NULL,
    size_sqft INT UNSIGNED NOT NULL,
    status_label VARCHAR(40) NOT NULL DEFAULT 'Available',
    image_path VARCHAR(255) NOT NULL,
    short_description TEXT NOT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    preferred_location VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

DELETE FROM properties;

INSERT INTO properties (title, location, price_ksh, bedrooms, bathrooms, size_sqft, status_label, image_path, short_description, featured) VALUES
('Skyline Glass Residence', 'Kilimani, Nairobi', 9900000, 5, 4, 4200, 'Signature Pick', 'assets/images/house-01.jpg', 'A dramatic city-facing residence with layered lighting, strong geometry, and resort-style entry detailing.', 1),
('Palm Court Duplex', 'Syokimau, Machakos', 8700000, 4, 3, 3100, 'Available', 'assets/images/house-02.jpg', 'A clean-lined duplex with balanced proportions, airy glazing, and a calm modern family layout.', 0),
('Urban Stone Retreat', 'Ruiru, Kiambu', 7600000, 4, 3, 2850, 'Available', 'assets/images/house-03.jpg', 'Warm timber accents, vertical greenery, and textured finishes define this polished contemporary home.', 0),
('Grand Facade Manor', 'Kitengela, Kajiado', 8400000, 4, 4, 3320, 'Available', 'assets/images/house-04.jpg', 'A bold front elevation with elevated detailing, sleek railings, and premium curb appeal throughout.', 0),
('Luna Frame Villa', 'Ruaka, Kiambu', 6500000, 3, 3, 2400, 'Available', 'assets/images/house-05.jpg', 'Compact yet refined, this villa delivers crisp monochrome styling with excellent daylight and flow.', 0),
('Midnight Horizon Estate', 'Karen, Nairobi', 9500000, 5, 5, 4600, 'Prime Luxury', 'assets/images/house-06.jpg', 'A high-impact statement home with poolside living, deep glazing, and impressive entertaining zones.', 0),
('Ivory Edge Residence', 'Athi River, Machakos', 5200000, 3, 2, 2150, 'Available', 'assets/images/house-07.jpg', 'An accessible modern home with a crisp facade, bright interior potential, and contemporary finishes.', 0);
