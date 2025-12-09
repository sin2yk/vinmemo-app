ALTER TABLE events
  ADD COLUMN revealed_at DATETIME NULL AFTER event_date;

ALTER TABLE events
  ADD COLUMN list_field_visibility JSON NULL AFTER revealed_at;

ALTER TABLE bottle_entries
  ADD COLUMN blind_reveal_level ENUM('none','country','country_vintage','full')
  NOT NULL DEFAULT 'none';
