<?php
// database.sql - Script para crear la base de datos
/*
CREATE DATABASE hackathon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE hackathon_db;

CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    document_type ENUM('cedula', 'pasaporte', 'cedula_escolar') NOT NULL,
    document_number VARCHAR(50) NOT NULL UNIQUE,
    nationality VARCHAR(1),
    birth_date DATE NOT NULL,
    age INT NOT NULL,
    gender ENUM('masculino', 'femenino') NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    state VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    education_level ENUM('primaria', 'bachillerato', 'universidad') NOT NULL,
    grade VARCHAR(100),
    category ENUM('Junior', 'Senior') NOT NULL,
    microbit_experience ENUM('ninguna', 'basica', 'intermedia', 'avanzada') NOT NULL,
    expectations TEXT,
    shirt_size ENUM('S', 'M', 'L', 'XL') NOT NULL,
    document_photo_path VARCHAR(500),
    is_minor BOOLEAN DEFAULT FALSE,
    guardian_name VARCHAR(255),
    guardian_doc_type VARCHAR(50),
    guardian_document VARCHAR(50),
    guardian_email VARCHAR(255),
    guardian_phone VARCHAR(20),
    authorization_doc_path VARCHAR(500),
    image_rights_accepted BOOLEAN DEFAULT TRUE,
    data_verified BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_document_number ON registrations(document_number);
CREATE INDEX idx_registration_number ON registrations(registration_number);
CREATE INDEX idx_email ON registrations(email);
*/
?>