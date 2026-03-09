-- Create database
CREATE DATABASE IF NOT EXISTS book_tracker;
USE book_tracker;

-- Create books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20),
    genre VARCHAR(100),
    publication_year INT,
    pages INT,
    current_page INT DEFAULT 0,
    status ENUM('To Read', 'Reading', 'Completed', 'On Hold', 'Dropped') DEFAULT 'To Read',
    rating DECIMAL(2,1),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create reading_sessions table
CREATE TABLE IF NOT EXISTS reading_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    session_date DATE NOT NULL,
    pages_read INT NOT NULL,
    minutes_read INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Insert some sample data
INSERT INTO books (title, author, genre, publication_year, pages, status) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 1925, 180, 'Completed'),
('To Kill a Mockingbird', 'Harper Lee', 'Fiction', 1960, 281, 'Reading'),
('1984', 'George Orwell', 'Dystopian', 1949, 328, 'To Read'),
('Pride and Prejudice', 'Jane Austen', 'Romance', 1813, 279, 'On Hold');