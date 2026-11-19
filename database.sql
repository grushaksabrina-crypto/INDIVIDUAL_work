CREATE DATABASE IF NOT EXISTS verdant_archive
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE verdant_archive;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS artworks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    artist VARCHAR(120) NOT NULL,
    medium VARCHAR(80) NOT NULL,
    dimensions VARCHAR(60) NOT NULL,
    creation_year SMALLINT UNSIGNED NOT NULL,
    status ENUM('exhibition', 'private', 'available') NOT NULL DEFAULT 'exhibition',
    description TEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_artworks_user FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO artworks (title, artist, medium, dimensions, creation_year, status, description, image_url) VALUES
(
    'The Hour Between',
    'Eleanor Vale',
    'Масло, холст',
    '60 × 80 см',
    2024,
    'exhibition',
    'Тихий свет удерживает равновесие на фоне бескрайнего сумеречного неба над горным озером.',
    'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=1000&q=85'
),
(
    'Villa di Vento Study',
    'Lorenzo Bellini',
    'Архитектурная графика',
    '50 × 70 см',
    2023,
    'private',
    'Этюд тосканской виллы в кипарисовых аллеях, выполненный в технике многослойной лессировки.',
    'https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?auto=format&fit=crop&w=1000&q=85'
),
(
    'Nocturne in Olive & Gold',
    'Clara Vance',
    'Темпера, дерево',
    '45 × 65 см',
    2025,
    'available',
    'Исследование светотени и патины старых мастеров в современной минималистичной раме.',
    'https://images.unsplash.com/photo-1577083552431-6e5fd01aa342?auto=format&fit=crop&w=1000&q=85'
);
