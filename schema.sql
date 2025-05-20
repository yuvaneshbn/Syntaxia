CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_price INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    college_name VARCHAR(255) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL,
    screenshot_path VARCHAR(255),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(100),
    location VARCHAR(255),
    description TEXT,
    image_url VARCHAR(255),
    attendees VARCHAR(100),
    refreshments VARCHAR(100),
    prizes VARCHAR(100),
    team_size VARCHAR(50),
    eligibility VARCHAR(255),
    requirements VARCHAR(255),
    price VARCHAR(100),
    seats_left INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
