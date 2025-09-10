-- MySQL schema for Bilty Management System
CREATE DATABASE IF NOT EXISTS bilty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bilty_db;

CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sample companies
INSERT IGNORE INTO companies (id, name) VALUES
(1, 'Acme Logistics'),
(2, 'FastTrans Freight'),
(3, 'City Couriers');

CREATE TABLE IF NOT EXISTS consignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  bilty_no VARCHAR(50) NOT NULL UNIQUE,
  date DATE NOT NULL,
  vehicle_no VARCHAR(50),
  driver_name VARCHAR(100),
  vehicle_type VARCHAR(50),
  sender_name VARCHAR(100),
  from_city VARCHAR(100),
  to_city VARCHAR(100),
  qty INT DEFAULT 0,
  details TEXT,
  km INT DEFAULT 0,
  rate DECIMAL(10,2) DEFAULT 0.00,
  amount DECIMAL(10,2) DEFAULT 0.00,
  advance DECIMAL(10,2) DEFAULT 0.00,
  balance DECIMAL(10,2) DEFAULT 0.00,
  CONSTRAINT fk_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;