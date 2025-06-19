-- Create the database
CREATE DATABASE IF NOT EXISTS mrejesho;
USE mrejesho;

-- Table: organizations
CREATE TABLE IF NOT EXISTS organizations (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    secret_code VARCHAR(50) NOT NULL UNIQUE,
    qr_code_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    qr_code_svg TEXT DEFAULT NULL,
    whatsapp_number VARCHAR(20) NOT NULL UNIQUE
);

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    organization_id INT DEFAULT NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
);

-- Table: feedback
CREATE TABLE IF NOT EXISTS feedback (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sender_number VARCHAR(20) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    rating INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
