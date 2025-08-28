-- Orphanage Management System Database Schema
-- Last updated: 2025-08-24 12:55:08 by joealjohn

-- Create Database
CREATE DATABASE IF NOT EXISTS orphanage_management;
USE orphanage_management;

-- Drop existing tables if they exist (for clean installation)
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS child_registrations;
DROP TABLE IF EXISTS adoption_requests;
DROP TABLE IF EXISTS children;
DROP TABLE IF EXISTS users;

-- Create Users Table
CREATE TABLE users (
                       user_id INT PRIMARY KEY AUTO_INCREMENT,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(100) NOT NULL UNIQUE,
                       password VARCHAR(100) NOT NULL,
                       role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                       status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
                       phone VARCHAR(20),
                       address TEXT,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Children Table
CREATE TABLE children (
                          child_id INT PRIMARY KEY AUTO_INCREMENT,
                          name VARCHAR(100) NOT NULL,
                          age INT NOT NULL,
                          gender ENUM('male', 'female', 'other') NOT NULL,
                          health_status TEXT,
                          education_level VARCHAR(50),
                          admission_date DATE NOT NULL,
                          photo VARCHAR(255),
                          status ENUM('available', 'adopted', 'pending') NOT NULL DEFAULT 'available',
                          background_info TEXT,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Adoption Requests Table
CREATE TABLE adoption_requests (
                                   request_id INT PRIMARY KEY AUTO_INCREMENT,
                                   user_id INT NOT NULL,
                                   child_id INT NOT NULL,
                                   reason TEXT NOT NULL,
                                   financial_status VARCHAR(50) NOT NULL,
                                   living_situation TEXT NOT NULL,
                                   contact_number VARCHAR(20) NOT NULL,
                                   address TEXT NOT NULL,
                                   status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                                   request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   approved_date TIMESTAMP NULL,
                                   rejection_reason TEXT,
                                   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                                   FOREIGN KEY (child_id) REFERENCES children(child_id) ON DELETE CASCADE
);

-- Create Child Registrations Table
CREATE TABLE child_registrations (
                                     reg_id INT PRIMARY KEY AUTO_INCREMENT,
                                     user_id INT NOT NULL,
                                     name VARCHAR(100) NOT NULL,
                                     age INT NOT NULL,
                                     gender ENUM('male', 'female', 'other') NOT NULL,
                                     health_status TEXT,
                                     education_level VARCHAR(50),
                                     photo VARCHAR(255),
                                     found_location TEXT,
                                     additional_info TEXT,
                                     status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                                     submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     processed_at TIMESTAMP NULL,
                                     rejection_reason TEXT,
                                     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create Contact Messages Table
CREATE TABLE contact_messages (
                                  message_id INT PRIMARY KEY AUTO_INCREMENT,
                                  name VARCHAR(100) NOT NULL,
                                  email VARCHAR(100) NOT NULL,
                                  subject VARCHAR(200) NOT NULL,
                                  message TEXT NOT NULL,
                                  status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
                                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                  read_at TIMESTAMP NULL,
                                  response TEXT
);

-- Create Notifications Table
CREATE TABLE notifications (
                               notification_id INT PRIMARY KEY AUTO_INCREMENT,
                               user_id INT NOT NULL,
                               title VARCHAR(100) NOT NULL,
                               message TEXT NOT NULL,
                               type VARCHAR(50) NOT NULL,
                               is_read BOOLEAN DEFAULT 0,
                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                               FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert Sample Admin User
INSERT INTO users (name, email, password, role, created_at) VALUES
    ('Admin User', 'admin@orphanage.com', 'admin123', 'admin', '2025-08-01 10:00:00');

-- Insert Sample Regular User
INSERT INTO users (name, email, password, role, phone, address, created_at) VALUES
    ('John Doe', 'john@example.com', 'password123', 'user', '+1234567890', '123 Main St, City, Country', '2025-08-05 14:30:00');

-- Insert Sample Children Records
INSERT INTO children (name, age, gender, health_status, education_level, admission_date, photo, status, background_info) VALUES
                                                                                                                             ('Sarah Johnson', 8, 'female', 'Healthy, regular check-ups required', 'Grade 3', '2024-03-15', 'assets/uploads/child1.jpg', 'available', 'Sarah was brought to the orphanage by social services. She is a bright child who loves reading and drawing.'),
                                                                                                                             ('Michael Brown', 5, 'male', 'Asthma, requires inhaler', 'Kindergarten', '2024-05-22', 'assets/uploads/child2.jpg', 'available', 'Michael has mild asthma that is well managed with his inhaler. He is very active and loves playing outdoors.'),
                                                                                                                             ('Emma Wilson', 10, 'female', 'Healthy', 'Grade 5', '2023-11-10', 'assets/uploads/child3.jpg', 'available', 'Emma is excellent in mathematics and science. She dreams of becoming a doctor when she grows up.'),
                                                                                                                             ('James Taylor', 7, 'male', 'Requires glasses for reading', 'Grade 2', '2024-01-08', 'assets/uploads/child4.jpg', 'available', 'James is a creative child who enjoys telling stories. He needs glasses only for reading.'),
                                                                                                                             ('Olivia Davis', 3, 'female', 'Healthy, follow-up nutrition plan', 'Preschool', '2024-07-01', 'assets/uploads/child5.jpg', 'available', 'Olivia is a happy toddler who is on a special nutrition plan to help her growth.'),
                                                                                                                             ('William Martin', 12, 'male', 'Recovered from minor surgery', 'Grade 7', '2023-09-18', 'assets/uploads/child6.jpg', 'pending', 'William is good at sports, especially swimming. He has recently recovered from a minor appendectomy.');

-- Insert Sample Adoption Request
INSERT INTO adoption_requests (user_id, child_id, reason, financial_status, living_situation, contact_number, address, status, request_date) VALUES
    (2, 3, 'I have always wanted to provide a loving home for a child who needs one. Emma seems like a wonderful child who would fit well with our family.', 'Middle Income', 'I live in a three-bedroom house with a yard. I have stable employment and work from home most days.', '+1234567890', '123 Main St, City, Country', 'pending', '2025-08-10 09:15:00');

-- Insert Sample Child Registration
INSERT INTO child_registrations (user_id, name, age, gender, health_status, education_level, photo, found_location, additional_info, status, submitted_at) VALUES
    (2, 'Daniel Moore', 4, 'male', 'Healthy, vaccinations up to date', 'Preschool', 'assets/uploads/registration1.jpg', 'Found near Central Park', 'Daniel was found by a park visitor. He appears to be well-cared for but was alone. He had a small toy car with him.', 'pending', '2025-08-15 14:20:00');

-- Insert Sample Contact Messages
INSERT INTO contact_messages (name, email, subject, message, status, created_at) VALUES
                                                                                     ('Jane Smith', 'jane@example.com', 'Volunteering Opportunities', 'I would like to know more about volunteering at your orphanage. What opportunities are available and how can I apply?', 'unread', '2025-08-18 10:45:00'),
                                                                                     ('Robert Johnson', 'robert@example.com', 'Donation Inquiry', 'Hello, I am interested in making a donation to support your work. Could you please provide information on how I can contribute?', 'read', '2025-08-17 16:30:00'),
                                                                                     ('Mary Williams', 'mary@example.com', 'Adoption Process', 'I am interested in adopting a child from your orphanage. Could you please explain the process and requirements?', 'unread', '2025-08-20 09:12:00');

-- Insert Sample Notifications
INSERT INTO notifications (user_id, title, message, type, created_at) VALUES
                                                                          (2, 'Adoption Request Update', 'Your adoption request for Emma Wilson has been received and is under review.', 'adoption_request', '2025-08-10 09:30:00'),
                                                                          (2, 'Registration Update', 'Thank you for registering Daniel Moore. Our team is reviewing the information provided.', 'registration', '2025-08-15 14:30:00');