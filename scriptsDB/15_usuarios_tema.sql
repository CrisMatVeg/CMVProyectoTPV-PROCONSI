-- Preferencias de tema por usuario
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS theme_mode ENUM('light','dark','black') NOT NULL DEFAULT 'light' AFTER rol,
    ADD COLUMN IF NOT EXISTS theme_accent VARCHAR(20) NOT NULL DEFAULT 'blue' AFTER theme_mode;

