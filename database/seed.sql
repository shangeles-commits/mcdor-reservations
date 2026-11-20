-- Update matching sample records instead of making duplicates when the seed is rerun.
INSERT INTO dining_tables (table_number, capacity, active) VALUES
    ('T1', 2, 1), ('T2', 4, 1), ('T3', 6, 1),
    ('T4', 8, 1), ('T5', 4, 1), ('T6', 6, 1)
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), active = VALUES(active);

INSERT INTO extras (name, price_per_guest, active) VALUES
    ('Extra Fries', 35.00, 1), ('Side Salad', 45.00, 1),
    ('Sundae', 30.00, 1), ('Soft Drink', 25.00, 1)
ON DUPLICATE KEY UPDATE price_per_guest = VALUES(price_per_guest), active = VALUES(active);

INSERT INTO menu_packages (name, description, price_per_guest, active) VALUES
    ('Burger with Fries and Drink', 'Classic burger meal served with fries and a drink.', 249.00, 1),
    ('Chicken Sandwich with Drink', 'Chicken sandwich served with a drink.', 199.00, 1),
    ('Fried Chicken and Spaghetti with Drink', 'Fried chicken, spaghetti, and a drink.', 279.00, 1),
    ('Burger Steak with Rice and Drink', 'Burger steak with rice and a drink.', 229.00, 1),
    ('Chicken Salad with Drink', 'Chicken salad served with a drink.', 189.00, 1),
    ('Fried Chicken with Fries and Drink', 'Fried chicken, fries, and a drink.', 259.00, 1),
    ('Fries with Sundae', 'A shareable fries and sundae combo.', 129.00, 1),
    ('Chicken Fillet with Rice and Drink', 'Chicken fillet with rice and a drink.', 219.00, 1),
    ('Barbeque Pork Sandwich with Drink', 'Barbeque pork sandwich served with a drink.', 209.00, 1),
    ('McDor Kids Meal (Chicken Fingers and Fries)', 'Kids meal with chicken fingers and fries.', 179.00, 1),
    ('McDor Weekly Special (Currently Spicy Chicken Fillet with Sundae and Drink)', 'Current special: spicy chicken fillet with sundae and a drink. Staff can edit this package.', 299.00, 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    price_per_guest = VALUES(price_per_guest),
    active = VALUES(active);
