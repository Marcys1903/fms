CREATE DATABASE IF NOT EXISTS financial_management;
USE financial_management;

CREATE TABLE access_levels (
    access_level_id INT AUTO_INCREMENT PRIMARY KEY,
    level_name VARCHAR(100) NOT NULL,
    description TEXT
);

INSERT INTO access_levels (level_name, description) VALUES
('Level 1 - System Core', 'Super Admin and IT System Administrator'),
('Level 2 - Financial Oversight', 'CFO and Finance Directors'),
('Level 3 - Operational Management', 'Accounting, Budget, Disbursement Officers'),
('Level 4 - Departmental Operations', 'Department Heads, Procurement, Asset Officers'),
('Level 5 - Monitoring and Verification', 'Auditors and Compliance Officers'),
('Level 6 - Requestors / Staff', 'End-users and request submitters');

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL
);

INSERT INTO roles (role_name) VALUES
('Super Administrator'),
('IT System Administrator'),
('Chief Financial Officer'),
('Accounting Officer'),
('Budget Officer'),
('Disbursement Officer'),
('Department Head'),
('Procurement Officer'),
('Asset Management Officer'),
('Internal Auditor'),
('Compliance Officer'),
('End User');

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(150) NOT NULL
);

CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(150),
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150),
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    access_level_id INT NOT NULL,
    department_id INT NULL,
    status ENUM('ACTIVE','INACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (access_level_id) REFERENCES access_levels(access_level_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE access_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    module_name VARCHAR(100),
    can_view BOOLEAN DEFAULT FALSE,
    can_create BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    module VARCHAR(100),
    action VARCHAR(255),
    ip_address VARCHAR(50),
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE security_compliance (
    compliance_id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(150),
    status ENUM('COMPLIANT','NON-COMPLIANT'),
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE budgets (
    budget_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT,
    project_id INT NULL,
    event_id INT NULL,
    fiscal_year YEAR,
    total_amount DECIMAL(15,2),
    status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED') DEFAULT 'DRAFT',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE budget_approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT,
    approver_id INT,
    approval_status ENUM('PENDING','APPROVED','REJECTED'),
    remarks TEXT,
    approval_date TIMESTAMP NULL,
    FOREIGN KEY (budget_id) REFERENCES budgets(budget_id),
    FOREIGN KEY (approver_id) REFERENCES users(user_id)
);

CREATE TABLE fund_allocations (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT,
    allocated_amount DECIMAL(15,2),
    allocation_date DATE,
    FOREIGN KEY (budget_id) REFERENCES budgets(budget_id)
);

CREATE TABLE revenues (
    revenue_id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(150),
    amount DECIMAL(15,2),
    revenue_date DATE,
    recorded_by INT,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

CREATE TABLE revenue_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    revenue_id INT,
    payment_status ENUM('PENDING','RECEIVED'),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revenue_id) REFERENCES revenues(revenue_id)
);

CREATE TABLE accounts_receivable (
    receivable_id INT AUTO_INCREMENT PRIMARY KEY,
    payer_name VARCHAR(150),
    description TEXT,
    amount_due DECIMAL(15,2),
    due_date DATE,
    status ENUM('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID'
);

CREATE TABLE receivable_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    receivable_id INT,
    message TEXT,
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receivable_id) REFERENCES accounts_receivable(receivable_id)
);

CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100),
    description TEXT,
    amount DECIMAL(15,2),
    expense_date DATE,
    recorded_by INT,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

CREATE TABLE vendors (
    vendor_id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_name VARCHAR(150),
    contact_info TEXT
);

CREATE TABLE accounts_payable (
    payable_id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT,
    invoice_number VARCHAR(100),
    amount DECIMAL(15,2),
    due_date DATE,
    status ENUM('UNPAID','PAID') DEFAULT 'UNPAID',
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id)
);

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    payable_id INT,
    payment_date DATE,
    amount DECIMAL(15,2),
    processed_by INT,
    FOREIGN KEY (payable_id) REFERENCES accounts_payable(payable_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
);

CREATE TABLE assets (
    asset_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(150),
    acquisition_date DATE,
    cost DECIMAL(15,2),
    condition_status VARCHAR(50)
);

CREATE TABLE asset_depreciation (
    depreciation_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT,
    depreciation_rate DECIMAL(5,2),
    current_value DECIMAL(15,2),
    last_updated DATE,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);

CREATE TABLE compliance_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(150),
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
);
