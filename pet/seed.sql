-- Initial Seed Data

USE pet_clinic;

-- Default Users (password is 'password123')
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2b$12$dadFyDyu7CrMIidYFdbW9OjO.C7pPJ91QS7asblDI7WupZOD544uG', 'System Admin', 'Admin'),
('frontdesk', '$2b$12$dadFyDyu7CrMIidYFdbW9OjO.C7pPJ91QS7asblDI7WupZOD544uG', 'Receptionist', 'Front Desk'),
('doctor1', '$2b$12$dadFyDyu7CrMIidYFdbW9OjO.C7pPJ91QS7asblDI7WupZOD544uG', 'Dr. Smith', 'Doctor'),
('staff1', '$2b$12$dadFyDyu7CrMIidYFdbW9OjO.C7pPJ91QS7asblDI7WupZOD544uG', 'Nurse Jane', 'Medical Staff'),
('accountant1', '$2b$12$dadFyDyu7CrMIidYFdbW9OjO.C7pPJ91QS7asblDI7WupZOD544uG', 'John Doe', 'Accountant');

-- Fee Master
INSERT INTO fee_master (fee_type, fee_amount, effective_from) VALUES
('registration', 500.00, CURDATE()),
('consultation', 300.00, CURDATE()),
('vaccination', 250.00, CURDATE());

-- Vaccination Master
INSERT INTO vaccination_master (vacc_name, pet_type, dosage_number, days_interval, min_age_days) VALUES
('DHPP', 'Dog', 1, 21, 42),
('FVRCP', 'Cat', 1, 21, 42),
('Rabies', 'Dog', 1, 365, 84);

-- Lab Categories
INSERT INTO lab_category (category_name, description) VALUES
('Hematology', 'Blood related tests'),
('Biochemistry', 'Chemical processes within and relating to living organisms');

-- Lab Tests
INSERT INTO lab_test_master (cat_id, test_name, test_code, fee) VALUES
(1, 'Complete Blood Count', 'CBC', 450.00),
(2, 'Glucose Test', 'GLU', 150.00);

-- Medicine Master
INSERT INTO medicine_master (med_name, med_category, dosage_form, strength, unit_price, stock_qty, reorder_level) VALUES
('Amoxicillin', 'Antibiotic', 'Tablet', '250mg', 10.00, 100, 20),
('Meloxicam', 'NSAID', 'Syrup', '1.5mg/ml', 50.00, 50, 10),
('IV Set', 'Disposable', 'Set', '', 150.00, 50, 10),
('DHPP Vaccine', 'Vaccine', 'Injection', '', 250.00, 30, 5);

-- Link Vaccine Master to Inventory
UPDATE vaccination_master SET med_id = (SELECT med_id FROM medicine_master WHERE med_name = 'DHPP Vaccine') WHERE vacc_name = 'DHPP';

-- Suppliers
INSERT INTO suppliers (supplier_name, contact_person, phone, email) VALUES
('VetPharma Ltd', 'Alice Green', '555-0199', 'alice@vetpharma.com'),
('Animal Care Supplies', 'Bob Brown', '555-0200', 'bob@acs.com');
