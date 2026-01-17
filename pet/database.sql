-- Database schema for Pet Clinic Management System

CREATE DATABASE IF NOT EXISTS pet_clinic;
USE pet_clinic;

-- 1. User Management
CREATE TABLE users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('Admin', 'Front Desk', 'Doctor', 'Medical Staff', 'Accountant') NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Master Data Tables
CREATE TABLE fee_master (
  fee_id INT PRIMARY KEY AUTO_INCREMENT,
  fee_type VARCHAR(50) NOT NULL, -- 'registration', 'consultation', 'vaccination', etc.
  fee_amount DECIMAL(10,2) NOT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE vaccination_master (
  vacc_id INT PRIMARY KEY AUTO_INCREMENT,
  vacc_name VARCHAR(100) NOT NULL,
  pet_type VARCHAR(50) NOT NULL,
  dosage_number INT NOT NULL,
  days_interval INT NOT NULL, -- Days between doses
  min_age_days INT,
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE lab_category (
  cat_id INT PRIMARY KEY AUTO_INCREMENT,
  category_name VARCHAR(100) NOT NULL,
  description TEXT
);

CREATE TABLE lab_test_master (
  test_id INT PRIMARY KEY AUTO_INCREMENT,
  cat_id INT,
  test_name VARCHAR(100) NOT NULL,
  test_code VARCHAR(50) UNIQUE,
  fee DECIMAL(10,2),
  FOREIGN KEY (cat_id) REFERENCES lab_category(cat_id)
);

CREATE TABLE lab_parameters (
  param_id INT PRIMARY KEY AUTO_INCREMENT,
  test_id INT,
  parameter_name VARCHAR(100) NOT NULL,
  pet_type VARCHAR(50), -- dog, cat, etc.
  pet_gender VARCHAR(10), -- male, female
  min_value DECIMAL(10,4),
  max_value DECIMAL(10,4),
  unit VARCHAR(50),
  FOREIGN KEY (test_id) REFERENCES lab_test_master(test_id)
);

CREATE TABLE medicine_master (
  med_id INT PRIMARY KEY AUTO_INCREMENT,
  med_name VARCHAR(200) NOT NULL,
  med_category VARCHAR(100),
  dosage_form VARCHAR(50), -- tablet, syrup, injection, etc.
  strength VARCHAR(50),
  unit_price DECIMAL(10,2),
  stock_qty INT DEFAULT 0,
  reorder_level INT,
  is_active BOOLEAN DEFAULT TRUE
);

-- 3. Front Desk Module
CREATE TABLE pet_registration (
  RegID INT PRIMARY KEY AUTO_INCREMENT,
  RegDt DATETIME NOT NULL,
  RegNo VARCHAR(100) UNIQUE NOT NULL, -- Format: YYYY-1000, YYYY-1001...

  -- Pet Details
  Pettyp VARCHAR(100) NOT NULL, -- Dog, Cat, Bird, etc.
  petnam VARCHAR(100) NOT NULL,
  petclr VARCHAR(100) NOT NULL,
  petsex VARCHAR(100) NOT NULL,
  petbred VARCHAR(100) NOT NULL,
  petage VARCHAR(100) NOT NULL,
  petwt DECIMAL(10,2) NOT NULL,
  pet_microchip VARCHAR(50),
  pet_dob DATE,

  -- Owner Details
  ownnam VARCHAR(100) NOT NULL,
  ownadd1 TEXT NOT NULL,
  ownadd2 TEXT NOT NULL,
  ownloc VARCHAR(100) NOT NULL,
  ownpin VARCHAR(100) NOT NULL,
  ownmob VARCHAR(100) NOT NULL,
  ownres VARCHAR(20) NOT NULL,
  ownemail VARCHAR(100) NOT NULL,
  ownalt_phone VARCHAR(20),

  -- System Fields
  registration_fee DECIMAL(10,2),
  Reports VARCHAR(10) NOT NULL DEFAULT '0',
  is_active BOOLEAN DEFAULT TRUE,
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_regno (RegNo),
  INDEX idx_ownmob (ownmob),
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE consultations (
  consult_id INT PRIMARY KEY AUTO_INCREMENT,
  RegNo VARCHAR(100) NOT NULL,
  consult_date DATETIME NOT NULL,
  consult_no VARCHAR(50) UNIQUE, -- Daily serial number
  doctor_id INT NOT NULL,
  current_weight DECIMAL(10,2),
  is_pet_present BOOLEAN DEFAULT TRUE,
  chief_complaint TEXT,
  consultation_fee DECIMAL(10,2),
  fee_waived BOOLEAN DEFAULT FALSE,
  status VARCHAR(50) DEFAULT 'scheduled', -- scheduled, in-progress, completed

  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (RegNo) REFERENCES pet_registration(RegNo),
  FOREIGN KEY (doctor_id) REFERENCES users(user_id),
  FOREIGN KEY (created_by) REFERENCES users(user_id),
  INDEX idx_consult_date (consult_date),
  INDEX idx_doctor (doctor_id)
);

CREATE TABLE payment_transactions (
  payment_id INT PRIMARY KEY AUTO_INCREMENT,
  RegNo VARCHAR(100) NOT NULL,
  consult_id INT,
  transaction_date DATETIME NOT NULL,
  receipt_no VARCHAR(50) UNIQUE,

  -- Payment Breakdown
  registration_fee DECIMAL(10,2) DEFAULT 0,
  consultation_fee DECIMAL(10,2) DEFAULT 0,
  medicine_charges DECIMAL(10,2) DEFAULT 0,
  lab_charges DECIMAL(10,2) DEFAULT 0,
  vaccination_charges DECIMAL(10,2) DEFAULT 0,
  xray_charges DECIMAL(10,2) DEFAULT 0,
  scan_charges DECIMAL(10,2) DEFAULT 0,
  surgery_charges DECIMAL(10,2) DEFAULT 0,
  other_charges DECIMAL(10,2) DEFAULT 0,

  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,

  -- Payment Mode Split
  cash_amount DECIMAL(10,2) DEFAULT 0,
  card_amount DECIMAL(10,2) DEFAULT 0,
  upi_amount DECIMAL(10,2) DEFAULT 0,
  credit_amount DECIMAL(10,2) DEFAULT 0, -- Add to credit queue
  advance_adjusted DECIMAL(10,2) DEFAULT 0,

  paid_amount DECIMAL(10,2) NOT NULL,
  balance_amount DECIMAL(10,2) DEFAULT 0,

  payment_source VARCHAR(100), -- Where amount received from
  notes TEXT,
  collected_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (RegNo) REFERENCES pet_registration(RegNo),
  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (collected_by) REFERENCES users(user_id),
  INDEX idx_receipt (receipt_no),
  INDEX idx_payment_date (transaction_date)
);

CREATE TABLE credit_transactions (
  credit_id INT PRIMARY KEY AUTO_INCREMENT,
  RegNo VARCHAR(100) NOT NULL,
  payment_id INT,
  credit_date DATE NOT NULL,
  credit_amount DECIMAL(10,2) NOT NULL,
  paid_amount DECIMAL(10,2) DEFAULT 0,
  balance DECIMAL(10,2) NOT NULL,
  due_date DATE,
  status VARCHAR(50) DEFAULT 'pending', -- pending, partial, paid
  notes TEXT,

  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (RegNo) REFERENCES pet_registration(RegNo),
  FOREIGN KEY (payment_id) REFERENCES payment_transactions(payment_id),
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE advance_payments (
  advance_id INT PRIMARY KEY AUTO_INCREMENT,
  RegNo VARCHAR(100) NOT NULL,
  advance_date DATE NOT NULL,
  advance_amount DECIMAL(10,2) NOT NULL,
  utilized_amount DECIMAL(10,2) DEFAULT 0,
  balance_amount DECIMAL(10,2) NOT NULL,
  receipt_no VARCHAR(50),

  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (RegNo) REFERENCES pet_registration(RegNo),
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 4. Doctor Module
CREATE TABLE vitals (
  vital_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,

  temperature DECIMAL(5,2),
  heart_rate INT,
  respiratory_rate INT,
  weight DECIMAL(10,2),
  body_condition_score INT,
  mucous_membrane VARCHAR(50),
  capillary_refill_time VARCHAR(20),
  hydration_status VARCHAR(50),

  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

CREATE TABLE diagnosis (
  diagnosis_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,

  diagnosis_notes TEXT NOT NULL,
  differential_diagnosis TEXT,
  treatment_plan TEXT,
  special_instructions TEXT,
  next_review_date DATE,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id)
);

CREATE TABLE prescriptions (
  prescription_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,
  med_id INT NOT NULL,

  medicine_name VARCHAR(200),
  quantity INT NOT NULL,
  dosage VARCHAR(100) NOT NULL,
  frequency VARCHAR(100) NOT NULL,
  duration VARCHAR(100) NOT NULL,
  route VARCHAR(50), -- Oral, Injection, Topical, etc.
  instructions TEXT,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (med_id) REFERENCES medicine_master(med_id)
);

CREATE TABLE lab_orders (
  order_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,
  test_id INT NOT NULL,

  test_name VARCHAR(100),
  test_fee DECIMAL(10,2),
  payment_status VARCHAR(50) DEFAULT 'pending',
  test_status VARCHAR(50) DEFAULT 'ordered', -- ordered, sample_collected, in_progress, completed
  priority VARCHAR(20) DEFAULT 'routine',

  ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  ordered_by INT,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (test_id) REFERENCES lab_test_master(test_id),
  FOREIGN KEY (ordered_by) REFERENCES users(user_id)
);

CREATE TABLE vaccination_orders (
  vacc_order_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,
  vacc_id INT NOT NULL,

  vaccine_name VARCHAR(100),
  dosage_number INT,
  administered BOOLEAN DEFAULT FALSE,
  administered_date DATE,
  next_due_date DATE,
  batch_number VARCHAR(50),
  injection_site VARCHAR(100),
  fee DECIMAL(10,2),
  payment_status VARCHAR(50) DEFAULT 'pending',

  ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  ordered_by INT,
  administered_by INT,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (vacc_id) REFERENCES vaccination_master(vacc_id),
  FOREIGN KEY (ordered_by) REFERENCES users(user_id),
  FOREIGN KEY (administered_by) REFERENCES users(user_id)
);

CREATE TABLE imaging_orders (
  imaging_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,

  imaging_type VARCHAR(50) NOT NULL, -- X-Ray, Ultrasound, CT, MRI
  body_part VARCHAR(100),
  clinical_indication TEXT,
  fee DECIMAL(10,2),
  payment_status VARCHAR(50) DEFAULT 'pending',
  status VARCHAR(50) DEFAULT 'ordered',

  findings TEXT,
  report_file VARCHAR(255),

  ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  ordered_by INT,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (ordered_by) REFERENCES users(user_id)
);

CREATE TABLE surgery_orders (
  surgery_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,

  procedure_name VARCHAR(200) NOT NULL,
  procedure_type VARCHAR(100),
  scheduled_date DATE,
  estimated_duration INT, -- minutes
  anesthesia_required BOOLEAN DEFAULT FALSE,

  procedure_notes TEXT,
  fee DECIMAL(10,2),
  payment_status VARCHAR(50) DEFAULT 'pending',
  status VARCHAR(50) DEFAULT 'scheduled',

  ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  ordered_by INT,

  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (ordered_by) REFERENCES users(user_id)
);

-- 5. Medical Staff Module
CREATE TABLE medicine_dispensing (
  dispense_id INT PRIMARY KEY AUTO_INCREMENT,
  prescription_id INT NOT NULL,
  consult_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,
  med_id INT NOT NULL,

  prescribed_qty INT NOT NULL,
  dispensed_qty INT NOT NULL,
  unit_price DECIMAL(10,2),
  total_amount DECIMAL(10,2),

  batch_number VARCHAR(50),
  expiry_date DATE,

  dispensed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  dispensed_by INT,

  FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id),
  FOREIGN KEY (med_id) REFERENCES medicine_master(med_id),
  FOREIGN KEY (dispensed_by) REFERENCES users(user_id)
);

CREATE TABLE lab_results (
  result_id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  param_id INT NOT NULL,
  RegNo VARCHAR(100) NOT NULL,

  parameter_name VARCHAR(100),
  result_value DECIMAL(10,4),
  unit VARCHAR(50),
  normal_range VARCHAR(100),
  is_abnormal BOOLEAN DEFAULT FALSE,
  remarks TEXT,

  tested_at DATETIME,
  tested_by INT,
  verified_by INT,
  verified_at DATETIME,

  FOREIGN KEY (order_id) REFERENCES lab_orders(order_id),
  FOREIGN KEY (param_id) REFERENCES lab_parameters(param_id),
  FOREIGN KEY (tested_by) REFERENCES users(user_id),
  FOREIGN KEY (verified_by) REFERENCES users(user_id)
);

CREATE TABLE vaccination_history (
  history_id INT PRIMARY KEY AUTO_INCREMENT,
  RegNo VARCHAR(100) NOT NULL,
  vacc_order_id INT NOT NULL,
  vacc_name VARCHAR(100) NOT NULL,
  dosage_number INT,
  administered_date DATE NOT NULL,
  next_due_date DATE,
  batch_number VARCHAR(50),
  injection_site VARCHAR(100),
  administered_by INT,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (RegNo) REFERENCES pet_registration(RegNo),
  FOREIGN KEY (vacc_order_id) REFERENCES vaccination_orders(vacc_order_id),
  FOREIGN KEY (administered_by) REFERENCES users(user_id)
);

-- Additional Service Charges
CREATE TABLE additional_service_charges (
  charge_id INT PRIMARY KEY AUTO_INCREMENT,
  consult_id INT NOT NULL,
  charge_type VARCHAR(100) NOT NULL, -- Nursing, Disposable, Assistant, etc.
  description TEXT,
  amount DECIMAL(10,2) NOT NULL,
  recorded_by INT,
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consult_id) REFERENCES consultations(consult_id),
  FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

-- Audit Trail
CREATE TABLE audit_log (
  log_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  action VARCHAR(255) NOT NULL,
  table_name VARCHAR(100),
  record_id INT,
  old_value TEXT,
  new_value TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);
