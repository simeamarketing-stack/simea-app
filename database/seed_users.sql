-- 2 fixed accounts. Default password for both: Simea@2026
-- CHANGE THIS PASSWORD after first login on each account (no self-service
-- password UI in this phase — see docs/DEPLOY.md for how to update it via
-- phpMyAdmin using PHP's password_hash() output).

INSERT INTO users (username, password_hash, full_name, role) VALUES
    ('quanly',  '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Quản lý SIMEA',  'quan_ly'),
    ('vanhanh', '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Vận hành SIMEA', 'van_hanh');
