CREATE DATABASE IF NOT EXISTS shift_assignment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shift_assignment;

CREATE TABLE IF NOT EXISTS employees (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contractHoursPerWeek DECIMAL(5,2) NOT NULL,
    skills TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS shifts (
    id VARCHAR(10) PRIMARY KEY,
    `start` VARCHAR(35) NOT NULL,
    `end` VARCHAR(35) NOT NULL,
    requiredSkill VARCHAR(50) NOT NULL,
    assignedEmployeeId VARCHAR(10) NULL,
    FOREIGN KEY (assignedEmployeeId) REFERENCES employees(id)
);

INSERT INTO employees (id, name, contractHoursPerWeek, skills) VALUES
('e1', 'Anna', 24, '["care", "medication"]'),
('e2', 'Bram', 32, '["care"]'),
('e3', 'Daan', 8, '["care", "medication"]'),
('e4', 'Fleur', 36, '["care", "night"]');

INSERT INTO shifts (id, `start`, `end`, requiredSkill, assignedEmployeeId) VALUES
('s1', '2026-11-02T07:00:00+01:00', '2026-11-02T15:00:00+01:00', 'care', 'e1'),
('s2', '2026-11-02T14:00:00+01:00', '2026-11-02T22:00:00+01:00', 'medication', NULL),
('s3', '2026-11-02T23:00:00+01:00', '2026-11-03T07:00:00+01:00', 'night', 'e4'),
('s4', '2026-11-03T07:00:00+01:00', '2026-11-03T15:00:00+01:00', 'care', 'e3'),
('s5', '2026-11-03T15:00:00+01:00', '2026-11-03T23:00:00+01:00', 'care', NULL),
('s6', '2026-11-04T07:00:00+01:00', '2026-11-04T15:00:00+01:00', 'medication', NULL);