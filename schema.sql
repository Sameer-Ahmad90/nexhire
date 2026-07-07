-- ============================================================
--  JOB PORTAL DATABASE  –  Phase 2 SQL Implementation
--  Based on EERD: 23F-3044 & 23F-3025
--  Compatible with: MySQL 5.7+ / MariaDB
-- ============================================================

-- Drop tables in reverse FK order
DROP TABLE IF EXISTS Hiring_Decision;
DROP TABLE IF EXISTS Offer;
DROP TABLE IF EXISTS Interview;
DROP TABLE IF EXISTS Full_Time_Job;
DROP TABLE IF EXISTS Part_Time_Job;
DROP TABLE IF EXISTS Application;
DROP TABLE IF EXISTS Resume;
DROP TABLE IF EXISTS Job_Posting;
DROP TABLE IF EXISTS Interviewer;
DROP TABLE IF EXISTS Candidate;
DROP TABLE IF EXISTS Company;

-- ============================================================
-- 1. COMPANY
-- ============================================================
CREATE TABLE Company (
    Company_Id      INT             NOT NULL AUTO_INCREMENT,
    Name            VARCHAR(150)    NOT NULL,
    Industry        VARCHAR(100),
    Location        VARCHAR(150),
    Website         VARCHAR(255),
    Founded_Year    YEAR,
    PRIMARY KEY (Company_Id)
);

-- ============================================================
-- 2. CANDIDATE
-- ============================================================
CREATE TABLE Candidate (
    Candidate_ID    INT             NOT NULL AUTO_INCREMENT,
    Name            VARCHAR(150)    NOT NULL,
    Email           VARCHAR(150)    NOT NULL UNIQUE,
    Phone           VARCHAR(20),
    Address         VARCHAR(255),
    DOB             DATE,
    PRIMARY KEY (Candidate_ID)
);

-- ============================================================
-- 3. RESUME  (Weak entity – partial key Resume_Seq per Candidate)
-- ============================================================
CREATE TABLE Resume (
    Candidate_ID    INT             NOT NULL,
    Resume_Seq      INT             NOT NULL,
    Education       TEXT,
    Certification   TEXT,
    Experience      TEXT,
    PRIMARY KEY (Candidate_ID, Resume_Seq),
    CONSTRAINT fk_resume_candidate FOREIGN KEY (Candidate_ID)
        REFERENCES Candidate(Candidate_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 4. JOB_POSTING
-- ============================================================
CREATE TABLE Job_Posting (
    Job_Id          INT             NOT NULL AUTO_INCREMENT,
    Company_Id      INT             NOT NULL,
    Title           VARCHAR(200)    NOT NULL,
    Description     TEXT,
    Salary_Range    VARCHAR(60),
    Employment_Type ENUM('Full-Time','Part-Time','Contract','Internship') NOT NULL,
    Posted_Date     DATE            NOT NULL,
    Deadline        DATE,
    PRIMARY KEY (Job_Id),
    CONSTRAINT fk_job_company FOREIGN KEY (Company_Id)
        REFERENCES Company(Company_Id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 5. JOB SPECIALISATION  – Full_Time_Job
-- ============================================================
CREATE TABLE Full_Time_Job (
    Job_Id          INT             NOT NULL,
    Benefits        TEXT,
    Notice_Period   VARCHAR(60),
    PRIMARY KEY (Job_Id),
    CONSTRAINT fk_ftj_job FOREIGN KEY (Job_Id)
        REFERENCES Job_Posting(Job_Id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 6. JOB SPECIALISATION  – Part_Time_Job
-- ============================================================
CREATE TABLE Part_Time_Job (
    Job_Id          INT             NOT NULL,
    Hours_Per_Week  DECIMAL(5,2),
    Shift_Type      VARCHAR(60),
    PRIMARY KEY (Job_Id),
    CONSTRAINT fk_ptj_job FOREIGN KEY (Job_Id)
        REFERENCES Job_Posting(Job_Id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 7. APPLICATION
-- ============================================================
CREATE TABLE Application (
    App_ID          INT             NOT NULL AUTO_INCREMENT,
    Candidate_ID    INT             NOT NULL,
    Job_ID          INT             NOT NULL,
    Resume_ID       INT,
    Applied_Date    DATE            NOT NULL,
    Status          ENUM('Pending','Reviewed','Shortlisted','Rejected','Accepted') NOT NULL DEFAULT 'Pending',
    Cover_Letter    TEXT,
    PRIMARY KEY (App_ID),
    CONSTRAINT fk_app_candidate FOREIGN KEY (Candidate_ID)
        REFERENCES Candidate(Candidate_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_app_job FOREIGN KEY (Job_ID)
        REFERENCES Job_Posting(Job_Id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 8. INTERVIEWER
-- ============================================================
CREATE TABLE Interviewer (
    Interviewer_ID  INT             NOT NULL AUTO_INCREMENT,
    Company_ID      INT             NOT NULL,
    Name            VARCHAR(150)    NOT NULL,
    Email           VARCHAR(150)    NOT NULL UNIQUE,
    Department      VARCHAR(100),
    PRIMARY KEY (Interviewer_ID),
    CONSTRAINT fk_ivr_company FOREIGN KEY (Company_ID)
        REFERENCES Company(Company_Id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 9. INTERVIEW
-- ============================================================
CREATE TABLE Interview (
    Interview_ID    INT             NOT NULL AUTO_INCREMENT,
    App_ID          INT             NOT NULL,
    Interviewer_ID  INT,
    Interview_Type  VARCHAR(80),
    Location_Link   VARCHAR(255),
    Schedule_Date   DATETIME        NOT NULL,
    PRIMARY KEY (Interview_ID),
    CONSTRAINT fk_int_app FOREIGN KEY (App_ID)
        REFERENCES Application(App_ID) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_int_ivr FOREIGN KEY (Interviewer_ID)
        REFERENCES Interviewer(Interviewer_ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ============================================================
-- 10. OFFER
-- ============================================================
CREATE TABLE Offer (
    Offer_ID        INT             NOT NULL AUTO_INCREMENT,
    App_ID          INT             NOT NULL,
    Offered_Salary  DECIMAL(12,2),
    Offer_Date      DATE            NOT NULL,
    Expiry_Date     DATE,
    PRIMARY KEY (Offer_ID),
    CONSTRAINT fk_offer_app FOREIGN KEY (App_ID)
        REFERENCES Application(App_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- 11. HIRING_DECISION
-- ============================================================
CREATE TABLE Hiring_Decision (
    Decision_ID     INT             NOT NULL AUTO_INCREMENT,
    Offer_ID        INT             NOT NULL,
    Decision        ENUM('Hired','Rejected','Pending') NOT NULL DEFAULT 'Pending',
    Decision_Date   DATE,
    Reason          TEXT,
    PRIMARY KEY (Decision_ID),
    CONSTRAINT fk_hd_offer FOREIGN KEY (Offer_ID)
        REFERENCES Offer(Offer_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO Company (Name, Industry, Location, Website, Founded_Year) VALUES
('TechNova Solutions',  'Technology',    'Karachi, Pakistan',  'https://technova.pk',   2015),
('FinEdge Corp',        'Finance',       'Lahore, Pakistan',   'https://finedge.com',   2010),
('MediCare Systems',    'Healthcare',    'Islamabad, Pakistan','https://medicare.pk',   2018),
('GreenBuild Ltd',      'Construction',  'Karachi, Pakistan',  'https://greenbuild.pk', 2008),
('EduSpark Academy',    'Education',     'Multan, Pakistan',   'https://eduspark.pk',   2020);

INSERT INTO Candidate (Name, Email, Phone, Address, DOB) VALUES
('Ali Hassan',      'ali.hassan@gmail.com',    '0301-1234567', 'Block-5, Gulshan, Karachi',    '1999-03-15'),
('Sara Khan',       'sara.khan@yahoo.com',     '0312-9876543', 'DHA Phase-4, Lahore',          '2000-07-22'),
('Usman Tariq',     'usman.tariq@outlook.com', '0333-5551234', 'F-7 Sector, Islamabad',        '1998-11-10'),
('Aisha Noor',      'aisha.noor@gmail.com',    '0345-8887766', 'Model Town, Lahore',           '2001-01-05'),
('Bilal Ahmed',     'bilal.ahmed@gmail.com',   '0321-4445566', 'North Nazimabad, Karachi',     '1997-06-30');

INSERT INTO Resume (Candidate_ID, Resume_Seq, Education, Certification, Experience) VALUES
(1, 1, 'BS Computer Science – FAST NUCES 2021',    'AWS Solutions Architect',         '2 years – Junior Developer at XYZ Tech'),
(2, 1, 'BBA Finance – IBA Karachi 2022',           'CFA Level 1',                     '1 year – Finance Intern at ABC Corp'),
(3, 1, 'MBBS – Aga Khan University 2020',          'USMLE Step 1',                    '3 years – House Officer at AKUH'),
(4, 1, 'BS Software Engineering – NUST 2022',      'Google Cloud Professional',       '1 year – Frontend Dev at StartupXYZ'),
(5, 1, 'BE Civil Engineering – NED University 2019','Project Management Professional','4 years – Site Engineer at GreenBuild');

INSERT INTO Job_Posting (Company_Id, Title, Description, Salary_Range, Employment_Type, Posted_Date, Deadline) VALUES
(1, 'Backend Developer',      'Django/Node.js development for SaaS products.',     '80k-120k PKR',  'Full-Time',  '2025-01-10', '2025-02-10'),
(1, 'Frontend Intern',        'React.js UI development with team mentorship.',      '30k-50k PKR',   'Internship', '2025-01-15', '2025-02-15'),
(2, 'Financial Analyst',      'Financial modelling and reporting role.',            '100k-150k PKR', 'Full-Time',  '2025-01-20', '2025-02-20'),
(3, 'Medical Officer',        'Outpatient consultations and ward duties.',          '120k-180k PKR', 'Full-Time',  '2025-02-01', '2025-03-01'),
(4, 'Site Supervisor',        'Oversee construction site daily operations.',        '70k-100k PKR',  'Part-Time',  '2025-02-05', '2025-03-05'),
(5, 'Content Creator',        'Produce educational content for digital platform.',  '40k-70k PKR',   'Part-Time',  '2025-02-10', '2025-03-10');

INSERT INTO Full_Time_Job (Job_Id, Benefits, Notice_Period) VALUES
(1, 'Health Insurance, Provident Fund, Annual Bonus',   '1 Month'),
(3, 'Health Insurance, Gratuity, 20 Days Annual Leave', '2 Months'),
(4, 'Health Insurance, Accommodation Allowance',        '1 Month');

INSERT INTO Part_Time_Job (Job_Id, Hours_Per_Week, Shift_Type) VALUES
(5, 20.00, 'Morning'),
(6, 15.00, 'Evening');

INSERT INTO Application (Candidate_ID, Job_ID, Resume_ID, Applied_Date, Status, Cover_Letter) VALUES
(1, 1, 1, '2025-01-12', 'Shortlisted', 'Passionate Django developer with 2 years of experience.'),
(2, 3, 1, '2025-01-22', 'Reviewed',    'Finance graduate eager to contribute to FinEdge.'),
(3, 4, 1, '2025-02-03', 'Pending',     'Experienced medical professional seeking growth.'),
(4, 2, 1, '2025-01-17', 'Accepted',    'Frontend enthusiast with React portfolio.'),
(5, 5, 1, '2025-02-07', 'Pending',     'Civil engineer with strong on-site leadership.');

INSERT INTO Interviewer (Company_ID, Name, Email, Department) VALUES
(1, 'Zara Malik',     'zara.malik@technova.pk',   'Engineering'),
(1, 'Hassan Qureshi', 'hassan.q@technova.pk',     'HR'),
(2, 'Nadia Siddiqui', 'nadia.s@finedge.com',      'Finance'),
(3, 'Dr. Kamran Ali', 'kamran.ali@medicare.pk',   'Medical'),
(4, 'Raza Butt',      'raza.butt@greenbuild.pk',  'Operations');

INSERT INTO Interview (App_ID, Interviewer_ID, Interview_Type, Location_Link, Schedule_Date) VALUES
(1, 1, 'Technical',  'meet.google.com/abc-xyz', '2025-01-20 10:00:00'),
(1, 2, 'HR Round',   'TechNova Office, Karachi', '2025-01-25 14:00:00'),
(2, 3, 'Case Study', 'Teams link: finedge.link', '2025-01-28 11:00:00'),
(4, 1, 'Portfolio',  'meet.google.com/def-uvw', '2025-01-22 09:00:00');

INSERT INTO Offer (App_ID, Offered_Salary, Offer_Date, Expiry_Date) VALUES
(1, 105000.00, '2025-02-01', '2025-02-15'),
(4, 40000.00,  '2025-01-25', '2025-02-05');

INSERT INTO Hiring_Decision (Offer_ID, Decision, Decision_Date, Reason) VALUES
(1, 'Hired',    '2025-02-10', 'Excellent technical skills and culture fit.'),
(2, 'Hired',    '2025-01-28', 'Strong React portfolio, ideal for intern role.');
