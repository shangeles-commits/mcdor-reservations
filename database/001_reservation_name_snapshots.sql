-- Run this update once on an existing database to save the names currently in related records.
ALTER TABLE reservations
    ADD COLUMN package_name_snapshot VARCHAR(120) NULL AFTER package_id,
    ADD COLUMN table_number_snapshot VARCHAR(20) NULL AFTER package_name_snapshot;

UPDATE reservations r
JOIN menu_packages p ON p.id = r.package_id
JOIN dining_tables t ON t.id = r.table_id
SET r.package_name_snapshot = p.name,
    r.table_number_snapshot = t.table_number;

ALTER TABLE reservations
    MODIFY COLUMN package_name_snapshot VARCHAR(120) NOT NULL,
    MODIFY COLUMN table_number_snapshot VARCHAR(20) NOT NULL;

ALTER TABLE reservation_extras
    ADD COLUMN extra_name_snapshot VARCHAR(100) NULL AFTER extra_id;

UPDATE reservation_extras re
JOIN extras e ON e.id = re.extra_id
SET re.extra_name_snapshot = e.name;

ALTER TABLE reservation_extras
    MODIFY COLUMN extra_name_snapshot VARCHAR(100) NOT NULL;
