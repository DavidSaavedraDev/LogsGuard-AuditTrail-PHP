-- LogsGuard - esquema inicial de auditoría
-- Compatible con MySQL 8+ y PostgreSQL 14+.

CREATE TABLE audit_logs (
    -- El backend genera este identificador antes de insertar el registro.
    id BIGINT PRIMARY KEY,
    username VARCHAR(120) NOT NULL,
    action VARCHAR(160) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    risk_level VARCHAR(10) NOT NULL DEFAULT 'low',
    event_type VARCHAR(80) NOT NULL,
    details TEXT NULL,
    CONSTRAINT audit_logs_risk_level_check
        CHECK (risk_level IN ('low', 'medium', 'high', 'critical'))
);

CREATE INDEX idx_audit_logs_occurred_at
    ON audit_logs (occurred_at);

CREATE INDEX idx_audit_logs_risk_level
    ON audit_logs (risk_level);

CREATE INDEX idx_audit_logs_event_type
    ON audit_logs (event_type);

-- Datos de demostración opcionales para validar el dashboard localmente.
INSERT INTO audit_logs
    (id, username, action, ip_address, occurred_at, risk_level, event_type, details)
VALUES
    (1, 'admin', 'Inicio de sesión exitoso', '192.168.1.10', CURRENT_TIMESTAMP, 'low', 'authentication', 'Acceso mediante credenciales válidas'),
    (2, 'api-service', 'Solicitud con token expirado', '10.0.0.24', CURRENT_TIMESTAMP, 'medium', 'authorization', 'El token JWT había expirado'),
    (3, 'unknown', 'Múltiples intentos fallidos', '203.0.113.42', CURRENT_TIMESTAMP, 'high', 'authentication', 'Se detectaron 8 intentos en 2 minutos');
