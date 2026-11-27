-- db() selects the database before running these idempotent table definitions.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_phone (phone),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_packages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL,
    price_per_guest DECIMAL(10,2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_menu_packages_name (name),
    CONSTRAINT chk_packages_price CHECK (price_per_guest >= 0),
    CONSTRAINT chk_packages_active CHECK (active IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS extras (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price_per_guest DECIMAL(10,2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_extras_name (name),
    CONSTRAINT chk_extras_price CHECK (price_per_guest >= 0),
    CONSTRAINT chk_extras_active CHECK (active IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dining_tables (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    table_number VARCHAR(20) NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dining_tables_number (table_number),
    CONSTRAINT chk_table_capacity CHECK (capacity BETWEEN 1 AND 8),
    CONSTRAINT chk_table_active CHECK (active IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    package_name_snapshot VARCHAR(120) NOT NULL,
    table_number_snapshot VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guest_count TINYINT UNSIGNED NOT NULL,
    package_price_snapshot DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(30) NOT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid',
    reservation_status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reservations_user (user_id),
    KEY idx_reservations_date (reservation_date),
    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_res_table FOREIGN KEY (table_id) REFERENCES dining_tables(id),
    CONSTRAINT fk_res_package FOREIGN KEY (package_id) REFERENCES menu_packages(id),
    CONSTRAINT chk_res_guests CHECK (guest_count BETWEEN 1 AND 8),
    CONSTRAINT chk_res_package_price CHECK (package_price_snapshot >= 0),
    CONSTRAINT chk_res_total CHECK (total_price >= 0),
    CONSTRAINT chk_res_payment CHECK (payment_status IN ('unpaid', 'simulated_paid')),
    CONSTRAINT chk_res_status CHECK (reservation_status IN ('active', 'cancelled', 'no_show'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservation_extras (
    reservation_id INT UNSIGNED NOT NULL,
    extra_id INT UNSIGNED NOT NULL,
    extra_name_snapshot VARCHAR(100) NOT NULL,
    extra_price_snapshot DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (reservation_id, extra_id),
    CONSTRAINT fk_re_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    CONSTRAINT fk_re_extra FOREIGN KEY (extra_id) REFERENCES extras(id),
    CONSTRAINT chk_re_price CHECK (extra_price_snapshot >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS occupied_slots (
    reservation_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    -- One active booking per table and time; cancelled/no-show bookings have no row here.
    PRIMARY KEY (table_id, reservation_date, reservation_time),
    UNIQUE KEY uq_occupied_reservation (reservation_id),
    CONSTRAINT fk_slot_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    CONSTRAINT fk_slot_table FOREIGN KEY (table_id) REFERENCES dining_tables(id)
) ENGINE=InnoDB;
