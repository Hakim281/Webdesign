

CREATE DATABASE IF NOT EXISTS luxestate_kenya
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE luxestate_kenya;


CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(150)        NOT NULL,
  email         VARCHAR(200)        NOT NULL UNIQUE,
  phone         VARCHAR(30)         NOT NULL,
  password_hash VARCHAR(255)        NOT NULL COMMENT 'Store bcrypt hash, never plain text',
  role          ENUM('client','agent','admin') DEFAULT 'client',
  is_verified   TINYINT(1)          DEFAULT 0,
  created_at    DATETIME            DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS properties (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(200)      NOT NULL,
  location        VARCHAR(200)      NOT NULL,
  neighbourhood   VARCHAR(100),
  price_ksh       DECIMAL(15,2)     NOT NULL,
  status          ENUM('available','sold','reserved') DEFAULT 'available',
  bedrooms        TINYINT           DEFAULT 0,
  bathrooms       TINYINT           DEFAULT 0,
  garages         TINYINT           DEFAULT 0,
  has_pool        TINYINT(1)        DEFAULT 0,
  size_sqft       INT,
  description     TEXT,
  badge_label     VARCHAR(50)       COMMENT 'e.g. Featured, New Listing, Exclusive',
  image_filename  VARCHAR(255),
  listed_by       INT               COMMENT 'FK → users.id (agent)',
  created_at      DATETIME          DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS enquiries (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT               COMMENT 'FK → users.id (nullable for guests)',
  property_id   INT               COMMENT 'FK → properties.id (nullable)',
  full_name     VARCHAR(150)      NOT NULL,
  email         VARCHAR(200)      NOT NULL,
  phone         VARCHAR(30),
  message       TEXT              NOT NULL,
  status        ENUM('new','contacted','closed') DEFAULT 'new',
  created_at    DATETIME          DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE SET NULL,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS saved_properties (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  property_id INT NOT NULL,
  saved_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_save (user_id, property_id),
  FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS sessions (
  id          VARCHAR(128)  PRIMARY KEY,
  user_id     INT           NOT NULL,
  ip_address  VARCHAR(45),
  user_agent  VARCHAR(255),
  created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  expires_at  DATETIME      NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;



-- Admin user (password: Admin@1234 — CHANGE THIS in production)
INSERT INTO users (full_name, email, phone, password_hash, role, is_verified)
VALUES ('LuxEstate Admin', 'admin@luxestatekenya.co.ke', '+254700000000',
        '$2y$12$exampleHashChangeMe', 'admin', 1);


INSERT INTO properties
  (title, location, neighbourhood, price_ksh, bedrooms, bathrooms, garages, has_pool, badge_label, image_filename)
VALUES
  ('The Karen Manor',     'Karen, Nairobi',       'Karen',       9500000, 5, 4, 2, 0, 'Featured',        'house1.jpg'),
  ('Westlands Elegance',  'Westlands, Nairobi',   'Westlands',   6800000, 4, 3, 1, 0, 'New Listing',     'house2.jpg'),
  ('Runda Dark Retreat',  'Runda, Nairobi',        'Runda',       7200000, 4, 3, 1, 0, 'Premium',         'house3.jpg'),
  ('Muthaiga Grand',      'Muthaiga, Nairobi',     'Muthaiga',   10000000, 6, 5, 2, 1, 'Exclusive',       'house4.jpg'),
  ('Lavington Heights',   'Lavington, Nairobi',    'Lavington',   5500000, 3, 2, 1, 0, 'Available',       'house5.jpg'),
  ('Gigiri Pool Estate',  'Gigiri, Nairobi',       'Gigiri',      9800000, 6, 5, 2, 1, 'Luxury',          'house6.jpg'),
  ('Spring Valley Gem',   'Spring Valley, Nairobi','Spring Valley',5000000, 3, 2, 1, 0, 'Affordable Luxury','house7.jpg');


