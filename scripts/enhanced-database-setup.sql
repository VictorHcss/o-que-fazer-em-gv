-- Melhorias no banco de dados
USE oque_fazer_gv;

-- Adicionar novas colunas para estabelecimentos
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS website VARCHAR(255);
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS email VARCHAR(255);
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS social_media JSON;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS menu_url VARCHAR(255);
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS delivery_available BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS reservation_required BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS parking_available BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS wifi_available BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS accessibility BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS pet_friendly BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS outdoor_seating BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS live_music BOOLEAN DEFAULT FALSE;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE;

-- Tabela de horários de funcionamento detalhados
CREATE TABLE IF NOT EXISTS establishment_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Domingo, 1=Segunda, etc.
    open_time TIME,
    close_time TIME,
    is_closed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_establishment_day (establishment_id, day_of_week)
);

-- Tabela de imagens dos estabelecimentos
CREATE TABLE IF NOT EXISTS establishment_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
);

-- Tabela de promoções
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5,2),
    discount_amount DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    terms_conditions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
);

-- Tabela de eventos
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
    current_bookings INT DEFAULT 0,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
);

-- Tabela de reservas de eventos
CREATE TABLE IF NOT EXISTS event_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    number_of_people INT DEFAULT 1,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de relatórios de problemas
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    establishment_id INT,
    review_id INT,
    type ENUM('inappropriate_content', 'fake_review', 'wrong_info', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'O Que Fazer em GV', 'Nome do site'),
('site_description', 'Descubra os melhores estabelecimentos de Governador Valadares', 'Descrição do site'),
('contact_email', 'contato@oquefazeremgv.com.br', 'Email de contato'),
('contact_phone', '(33) 99999-9999', 'Telefone de contato'),
('max_reviews_per_user_per_day', '5', 'Máximo de avaliações por usuário por dia'),
('featured_establishments_limit', '6', 'Limite de estabelecimentos em destaque'),
('enable_events', '1', 'Habilitar sistema de eventos'),
('enable_promotions', '1', 'Habilitar sistema de promoções');

-- Índices adicionais para performance
CREATE INDEX IF NOT EXISTS idx_establishments_featured ON establishments(featured, is_active);
CREATE INDEX IF NOT EXISTS idx_establishments_view_count ON establishments(view_count);
CREATE INDEX IF NOT EXISTS idx_promotions_active ON promotions(is_active, start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date, is_active);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_reports_status ON reports(status, created_at);

-- Inserir dados de exemplo para horários
INSERT IGNORE INTO establishment_hours (establishment_id, day_of_week, open_time, close_time) VALUES
(1, 1, '11:00:00', '22:00:00'), -- Segunda
(1, 2, '11:00:00', '22:00:00'), -- Terça
(1, 3, '11:00:00', '22:00:00'), -- Quarta
(1, 4, '11:00:00', '22:00:00'), -- Quinta
(1, 5, '11:00:00', '23:00:00'), -- Sexta
(1, 6, '11:00:00', '23:00:00'), -- Sábado
(1, 0, '11:00:00', '22:00:00'); -- Domingo

-- Inserir promoção de exemplo
INSERT IGNORE INTO promotions (establishment_id, title, description, discount_percentage, start_date, end_date) VALUES
(1, 'Happy Hour Especial', 'Desconto de 20% em bebidas das 17h às 19h', 20.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Inserir evento de exemplo
INSERT IGNORE INTO events (establishment_id, title, description, event_date, start_time, end_time, price) VALUES
(3, 'Noite de Samba', 'Apresentação especial de samba com artistas locais', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '20:00:00', '23:00:00', 15.00);

-- Atualizar estabelecimentos com novos campos
UPDATE establishments SET 
    delivery_available = TRUE,
    wifi_available = TRUE,
    parking_available = TRUE
WHERE id IN (1, 2);

UPDATE establishments SET 
    live_music = TRUE,
    outdoor_seating = TRUE
WHERE id = 3;

UPDATE establishments SET 
    wifi_available = TRUE,
    outdoor_seating = TRUE
WHERE id = 4;

UPDATE establishments SET 
    accessibility = TRUE,
    parking_available = TRUE
WHERE id = 5;

-- Marcar alguns estabelecimentos como destaque
UPDATE establishments SET featured = TRUE WHERE id IN (1, 2, 3);
