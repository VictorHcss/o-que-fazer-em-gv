-- Create database
CREATE DATABASE IF NOT EXISTS oquefazeremgv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oquefazeremgv;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(500) NULL,
    phone VARCHAR(20) NULL,
    birth_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Establishments table
CREATE TABLE IF NOT EXISTS establishments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('restaurante', 'bar', 'cafe', 'cultura', 'entretenimento') NOT NULL,
    image_url VARCHAR(500),
    address TEXT,
    neighborhood VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(500),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    opening_hours VARCHAR(255),
    price_range TINYINT DEFAULT 1,
    features JSON,
    is_open BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_neighborhood (neighborhood),
    INDEX idx_featured (featured),
    INDEX idx_active (is_active)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    establishment_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_establishment (user_id, establishment_id),
    INDEX idx_establishment (establishment_id),
    INDEX idx_rating (rating)
);

-- User favorites table
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    establishment_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_favorite (user_id, establishment_id)
);

-- Promotions table
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5,2),
    discount_amount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    terms_conditions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (is_active)
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    price DECIMAL(10,2),
    max_capacity INT,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date),
    INDEX idx_active (is_active)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator') DEFAULT 'moderator',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO establishments (name, description, category, image_url, address, neighborhood, phone, latitude, longitude, opening_hours, price_range, features, is_open, featured) VALUES
('Restaurante Mineiro Tradicional', 'Autêntica culinária mineira com pratos tradicionais e ambiente acolhedor no coração de Governador Valadares.', 'restaurante', '/placeholder.svg?height=220&width=350&text=Restaurante+Mineiro', 'Rua Marechal Floriano, 123 - Centro', 'centro', '(33) 3271-1234', -18.8506, -41.9492, '11:00-22:00', 2, '["Estacionamento", "Wi-Fi", "Ar Condicionado"]', TRUE, TRUE),
('Pizzaria Panorâmica', 'Pizzas artesanais com vista panorâmica da cidade. Ambiente romântico e familiar.', 'restaurante', '/placeholder.svg?height=220&width=350&text=Pizzaria+Panorâmica', 'Av. Minas Gerais, 456 - Ibituruna', 'ibituruna', '(33) 3271-5678', -18.8456, -41.9442, '18:00-23:00', 2, '["Vista Panorâmica", "Delivery", "Música Ao Vivo"]', TRUE, TRUE),
('Bar Música Ao Vivo', 'O melhor da música ao vivo em GV. Ambiente descontraído com shows todas as sextas e sábados.', 'bar', '/placeholder.svg?height=220&width=350&text=Bar+Música+Ao+Vivo', 'Rua Coronel Teodolino, 789 - Centro', 'centro', '(33) 3271-9012', -18.8516, -41.9502, '17:00-02:00', 2, '["Música Ao Vivo", "Happy Hour", "Área Externa"]', TRUE, TRUE),
('Café Italiano Aconchegante', 'Café artesanal com grãos especiais e ambiente acolhedor. Perfeito para trabalhar ou relaxar.', 'cafe', '/placeholder.svg?height=220&width=350&text=Café+Italiano', 'Rua Santos Dumont, 321 - Santa Terezinha', 'santa-terezinha', '(33) 3271-3456', -18.8476, -41.9462, '07:00-19:00', 1, '["Wi-Fi", "Café Especial", "Ambiente Silencioso"]', TRUE, FALSE),
('Casa de Shows Municipal', 'Teatro municipal com programação cultural diversificada. Shows, peças teatrais e eventos culturais.', 'cultura', '/placeholder.svg?height=220&width=350&text=Casa+de+Shows', 'Praça dos Pioneiros, 100 - Centro', 'centro', '(33) 3271-7890', -18.8496, -41.9482, '19:00-23:00', 2, '["Ar Condicionado", "Estacionamento", "Acessibilidade"]', FALSE, FALSE),
('Balada Eletrônica GV', 'A melhor música eletrônica da cidade. DJs renomados e pista de dança moderna.', 'bar', '/placeholder.svg?height=220&width=350&text=Balada+Eletrônica', 'Av. Brasil, 567 - Grã Duquesa', 'grã-duquesa', '(33) 3271-2468', -18.8526, -41.9512, '22:00-05:00', 3, '["Pista de Dança", "Som Profissional", "Área VIP"]', FALSE, FALSE),
('Churrascaria do Gaúcho', 'Churrasco tradicional gaúcho com carnes nobres e buffet completo.', 'restaurante', '/placeholder.svg?height=220&width=350&text=Churrascaria+Gaúcho', 'Av. Brasil, 234 - Centro', 'centro', '(33) 3271-4567', -18.8520, -41.9480, '11:30-15:00, 18:30-23:00', 3, '["Rodízio", "Buffet", "Estacionamento"]', TRUE, FALSE),
('Lanchonete da Esquina', 'Lanches artesanais e sucos naturais. Ambiente familiar e preços acessíveis.', 'restaurante', '/placeholder.svg?height=220&width=350&text=Lanchonete+Esquina', 'Rua das Flores, 567 - Ibituruna', 'ibituruna', '(33) 3271-8901', -18.8470, -41.9450, '06:00-22:00', 1, '["Delivery", "Sucos Naturais", "Preço Justo"]', TRUE, FALSE);

-- Insert sample reviews
INSERT INTO reviews (user_id, establishment_id, rating, comment) VALUES
(1, 1, 5, 'Comida excelente e atendimento impecável!'),
(1, 2, 4, 'Pizza muito boa e vista linda da cidade.'),
(1, 3, 5, 'Ambiente incrível, música ao vivo de qualidade!');

-- Insert sample promotions
INSERT INTO promotions (establishment_id, title, description, discount_percentage, start_date, end_date) VALUES
(2, 'Pizza em Dobro', 'Compre uma pizza grande e ganhe outra igual grátis!', 50, '2024-01-01', '2024-12-31'),
(3, 'Happy Hour Especial', 'Cerveja e petiscos com 30% de desconto das 17h às 19h', 30, '2024-01-01', '2024-12-25');

-- Insert sample events
INSERT INTO events (establishment_id, title, description, event_date, start_time, price) VALUES
(5, 'Show de Rock Nacional', 'Grandes bandas do rock nacional se apresentam em uma noite inesquecível', '2024-12-20', '20:00:00', 25.00),
(3, 'Noite do Samba', 'Samba de raiz com os melhores músicos da região', '2024-12-22', '21:00:00', 15.00);

-- Insert admin user (password: admin123)
INSERT INTO admin_users (username, email, password, role) VALUES
('admin', 'admin@oquefazeremgv.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_establishments_location ON establishments(latitude, longitude);
CREATE INDEX idx_reviews_rating_date ON reviews(rating, created_at);
CREATE INDEX idx_promotions_dates ON promotions(start_date, end_date, is_active);
CREATE INDEX idx_events_date ON events(event_date, is_active);
