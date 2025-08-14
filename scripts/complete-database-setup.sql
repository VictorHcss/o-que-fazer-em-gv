-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS oque_fazer_gv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oque_fazer_gv;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de estabelecimentos
CREATE TABLE IF NOT EXISTS establishments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    image_url VARCHAR(500),
    address VARCHAR(500),
    neighborhood VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    price_range TINYINT DEFAULT 1,
    opening_hours VARCHAR(255),
    is_open BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de avaliações
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    establishment_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
);

-- Tabela de favoritos
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    establishment_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, establishment_id)
);

-- Tabela de configurações de privacidade
CREATE TABLE IF NOT EXISTS user_privacy_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_public BOOLEAN DEFAULT TRUE,
    show_reviews BOOLEAN DEFAULT TRUE,
    show_favorites BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
);

-- Índices para melhor performance
CREATE INDEX idx_establishments_category ON establishments(category);
CREATE INDEX idx_establishments_neighborhood ON establishments(neighborhood);
CREATE INDEX idx_establishments_active ON establishments(is_active);
CREATE INDEX idx_reviews_establishment ON reviews(establishment_id);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_favorites_user ON user_favorites(user_id);
CREATE INDEX idx_favorites_establishment ON user_favorites(establishment_id);

-- Inserir dados de exemplo
INSERT INTO establishments (name, description, category, image_url, address, neighborhood, phone, latitude, longitude, price_range, opening_hours, features) VALUES
('Restaurante Mineiro Tradicional', 'Autêntica culinária mineira com pratos tradicionais e ambiente acolhedor no coração de Governador Valadares.', 'restaurante', '/placeholder.svg?height=200&width=350', 'Rua Marechal Floriano, 123 - Centro', 'centro', '(33) 3271-1234', -18.8506, -41.9492, 2, '11:00-22:00', '["Estacionamento", "Wi-Fi", "Ar Condicionado"]'),

('Pizzaria Panorâmica', 'Pizzas artesanais com vista panorâmica da cidade. Ambiente romântico e familiar.', 'restaurante', '/placeholder.svg?height=200&width=350', 'Av. Minas Gerais, 456 - Ibituruna', 'ibituruna', '(33) 3271-5678', -18.8456, -41.9442, 2, '18:00-23:00', '["Vista Panorâmica", "Delivery", "Música Ao Vivo"]'),

('Bar Música Ao Vivo', 'O melhor da música ao vivo em GV. Ambiente descontraído com shows todas as sextas e sábados.', 'bar', '/placeholder.svg?height=200&width=350', 'Rua Coronel Teodolino, 789 - Centro', 'centro', '(33) 3271-9012', -18.8516, -41.9502, 2, '17:00-02:00', '["Música Ao Vivo", "Happy Hour", "Área Externa"]'),

('Café Italiano Aconchegante', 'Café artesanal com grãos especiais e ambiente acolhedor. Perfeito para trabalhar ou relaxar.', 'cafe', '/placeholder.svg?height=200&width=350', 'Rua Santos Dumont, 321 - Santa Terezinha', 'santa-terezinha', '(33) 3271-3456', -18.8476, -41.9462, 1, '07:00-19:00', '["Wi-Fi", "Café Especial", "Ambiente Silencioso"]'),

('Casa de Shows Municipal', 'Teatro municipal com programação cultural diversificada. Shows, peças teatrais e eventos culturais.', 'cultura', '/placeholder.svg?height=200&width=350', 'Praça dos Pioneiros, 100 - Centro', 'centro', '(33) 3271-7890', -18.8496, -41.9482, 2, '19:00-23:00', '["Ar Condicionado", "Estacionamento", "Acessibilidade"]'),

('Balada Eletrônica GV', 'A melhor música eletrônica da cidade. DJs renomados e pista de dança moderna.', 'bar', '/placeholder.svg?height=200&width=350', 'Av. Brasil, 567 - Grã Duquesa', 'grã-duquesa', '(33) 3271-2468', -18.8526, -41.9512, 3, '22:00-05:00', '["Pista de Dança", "Som Profissional", "Área VIP"]');

-- Inserir usuário de exemplo
INSERT INTO users (name, email, password) VALUES 
('João Silva', 'joao@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Inserir avaliações de exemplo
INSERT INTO reviews (user_id, establishment_id, rating, comment) VALUES
(1, 1, 5, 'Excelente comida mineira! O feijão tropeiro estava perfeito e o atendimento foi muito bom.'),
(1, 2, 4, 'Pizza muito boa e a vista é realmente incrível. Recomendo para um jantar romântico.'),
(1, 3, 5, 'Ambiente fantástico! A banda estava excelente e o som é de qualidade. Voltarei sempre!');

-- Inserir favoritos de exemplo
INSERT INTO user_favorites (user_id, establishment_id) VALUES
(1, 1),
(1, 3);

-- Inserir configurações de privacidade padrão
INSERT INTO user_privacy_settings (user_id, profile_public, show_reviews, show_favorites, email_notifications) VALUES
(1, 1, 1, 1, 1);
