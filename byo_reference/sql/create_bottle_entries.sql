-- DBは gs_wineparty を想定
-- 先に phpMyAdmin などで
-- CREATE DATABASE gs_wineparty CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- を実行しておくこと

USE gs_wineparty;

DROP TABLE IF EXISTS bottle_entries;

CREATE TABLE bottle_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  wine_producer VARCHAR(255) NOT NULL,
  wine_name VARCHAR(255) NOT NULL,
  wine_vintage VARCHAR(10) NOT NULL,
  wine_type ENUM('sparkling','white','red','rose','other') NOT NULL,
  price_band ENUM('under3000','3000-4999','5000-7999','8000-11999','12000-19999','20000up') NOT NULL,
  theme_fit TINYINT NOT NULL COMMENT '1〜5の5段階評価',
  blind_level TINYINT NOT NULL DEFAULT 0 COMMENT '0=フル表示,1=銘柄のみマスク,2=銘柄+ヴィンテージマスク',
  blind_password_hash VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
