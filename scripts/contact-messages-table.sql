-- Tabela para armazenar mensagens de contato
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_email (email)
);

-- Inserir algumas mensagens de exemplo (opcional)
INSERT INTO contact_messages (name, email, subject, message, status) VALUES
('João Silva', 'joao@email.com', 'Sugestão de Estabelecimento', 'Gostaria de sugerir a inclusão do Restaurante do João na plataforma.', 'unread'),
('Maria Santos', 'maria@email.com', 'Problema no Site', 'Estou tendo dificuldades para fazer login na plataforma.', 'read'),
('Pedro Costa', 'pedro@email.com', 'Elogio', 'Parabéns pela plataforma! Muito útil para descobrir novos lugares.', 'replied');
