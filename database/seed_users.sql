-- 5 fixed accounts, one per role. Default password for all: Simea@2026
-- CHANGE THIS PASSWORD after first login on each account (no self-service
-- password UI in this phase — see docs/DEPLOY.md for how to update it via
-- phpMyAdmin using PHP's password_hash() output).

INSERT INTO users (username, password_hash, full_name, role) VALUES
    ('lanhdao',  '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Lãnh đạo SIMEA',  'lanh_dao'),
    ('dieuphoi', '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Điều phối SIMEA', 'dieu_phoi'),
    ('xuong',    '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Xưởng SIMEA',     'xuong'),
    ('kho',      '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'Kho SIMEA',       'kho'),
    ('qc',       '$2y$10$A.p/cEra3b4UOQWui4wZ3eQTEhHvEg2Rg63b9f9HAWjYsE819Jdwm', 'QC SIMEA',        'qc');
