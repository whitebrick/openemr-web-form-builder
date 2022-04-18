CREATE TABLE IF NOT EXISTS cforms (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    form_type text,
    created_at datetime default now(),
    pid BIGINT,
    fname text,
    lname text,
    dob date,
    data JSON
)  ENGINE=INNODB;
