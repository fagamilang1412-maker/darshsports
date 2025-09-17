CREATE DATABASE darsh_sports;
USE darsh_sports;

CREATE TABLE parties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    party_name VARCHAR(255) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    delivery_date DATE NOT NULL,
    fabric_type VARCHAR(50) NOT NULL,
    collar_type VARCHAR(100) NOT NULL,
    sleeve_type VARCHAR(50) NOT NULL,
    sublimation_collar BOOLEAN DEFAULT FALSE,
    sublimation_sleeve BOOLEAN DEFAULT FALSE,
    party_photo LONGBLOB,
    status ENUM('progress', 'ready', 'delivered') DEFAULT 'progress',
    payment_status ENUM('Unpaid', 'Remain', 'Fully Paid') DEFAULT 'Unpaid',
    payment_method ENUM('Cash', 'Online') DEFAULT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tshirt_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    party_id INT NOT NULL,
    player_name VARCHAR(255),
    t_no VARCHAR(50),
    size VARCHAR(10) NOT NULL,
    sleeve_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE
);