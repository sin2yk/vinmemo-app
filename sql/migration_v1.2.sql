-- sql/migration_v1.2.sql

-- 1. Add event_type and venue_id to events
-- Note: DB uses 'title' and 'event_date' currently, not 'name' and 'date_time'. 
-- We add columns accordingly.
ALTER TABLE events
  ADD COLUMN event_type VARCHAR(16) NOT NULL DEFAULT 'BYO' AFTER title,
  ADD COLUMN venue_id INT NULL; 
  -- Cannot use 'AFTER organizer_user_id' because it doesn't exist in current DB.

-- 2. Create venues table
CREATE TABLE IF NOT EXISTS venues (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  venue_type VARCHAR(50) NOT NULL DEFAULT 'restaurant',
  address VARCHAR(255) NULL,
  city VARCHAR(100) NULL,
  country VARCHAR(100) NULL,
  website_url VARCHAR(255) NULL,
  owner_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Extend bottle_entries
-- Note: Existing columns verify OK.
ALTER TABLE bottle_entries
  ADD COLUMN participant_id INT NULL AFTER event_id,
  ADD COLUMN brought_by_type VARCHAR(32) NULL AFTER participant_id,
  ADD COLUMN brought_by_user_id INT NULL AFTER brought_by_type,
  ADD COLUMN brought_by_venue_id INT NULL AFTER brought_by_user_id;

-- 4. Create event_participants table
CREATE TABLE IF NOT EXISTS event_participants (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NULL,
  display_name VARCHAR(255) NOT NULL,
  role_in_event VARCHAR(32) NOT NULL DEFAULT 'guest',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
