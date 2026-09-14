-- 8 fixed material groups. Names are a reasonable starting point for a
-- coffee-capsule production line — rename directly via UPDATE if they
-- don't match SIMEA's real material categories before go-live.

INSERT INTO material_groups (code, name, sort_order) VALUES
    ('NVL_CF',  'Cà phê nguyên liệu', 1),
    ('VO_CUP',  'Vỏ cup',             2),
    ('NAP_CUP', 'Nắp cup',            3),
    ('TEM',     'Tem nhãn',           4),
    ('SEAL',    'Màng seal',          5),
    ('HOP',     'Hộp giấy',           6),
    ('THUNG',   'Thùng carton',       7),
    ('KHAC',    'Vật tư phụ trợ khác', 8);
