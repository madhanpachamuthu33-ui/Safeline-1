-- SafeLine database schema
-- Import this file into MySQL before running the app:
--   mysql -u root -p < schema.sql


-- Admin accounts (reviewers who log in to the dashboard)
CREATE TABLE IF NOT EXISTS admins (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    username              VARCHAR(50)  NOT NULL UNIQUE,
    password_hash         VARCHAR(255) NOT NULL,
    security_question     VARCHAR(255) NOT NULL,
    security_answer_hash  VARCHAR(255) NOT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Anonymous reports
CREATE TABLE IF NOT EXISTS reports (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(20)  NOT NULL UNIQUE,
    category      VARCHAR(50)  NOT NULL,
    severity      VARCHAR(20)  NOT NULL,
    location      VARCHAR(255) NULL,
    occurred_at   DATETIME     NULL,
    description   TEXT         NOT NULL,
    contact_info  VARCHAR(255) NULL,
    image_path    VARCHAR(255) NULL,
    status        VARCHAR(20)  NOT NULL DEFAULT 'Received',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Status change log per report (for the tracking timeline)
CREATE TABLE IF NOT EXISTS report_status_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    report_id   INT NOT NULL,
    status      VARCHAR(20) NOT NULL,
    changed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;
