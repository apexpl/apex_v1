

DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS images_contents;
DROP TABLE IF EXISTS images;
DROP TABLE IF EXISTS notifications_login_notices;
DROP TABLE IF EXISTS notifications_queue;
DROP TABLE IF EXISTS notifications_mass_queue;
DROP TABLE IF EXISTS notifications_attachments;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS encrypt_data_keys;
DROP TABLE IF EXISTS encrypt_keys;
DROP TABLE IF EXISTS encrypt_data;
DROP TABLE IF EXISTS encrypt_pgp_keys;
DROP TABLE IF EXISTS auth_history_pages;
DROP TABLE IF EXISTS auth_history;
DROP TABLE IF EXISTS auth_allowips;
DROP TABLE IF EXISTS cms_pages;
DROP TABLE IF EXISTS cms_menus;
DROP TABLE IF EXISTS cms_placeholders;
DROP TABLE IF EXISTS internal_upgrades;
DROP TABLE IF EXISTS internal_boxlists;
DROP TABLE IF EXISTS internal_crontab;
DROP TABLE IF EXISTS internal_components;
DROP TABLE IF EXISTS internal_packages;
DROP TABLE IF EXISTS internal_repos;
DROP TABLE IF EXISTS internal_themes;
DROP TABLE IF EXISTS internal_languages;
DROP TABLE IF EXISTS internal_transactions;
DROP TABLE IF EXISTS internal_file_hashes;
DROP TABLE IF EXISTS dashboard_profiles_items;
DROP TABLE IF EXISTS dashboard_profiles;
DROP TABLE IF EXISTS dashboard_items;


--------------------------------------------------
-- Internal tables
--------------------------------------------------


CREATE TABLE internal_repos (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    is_local TINYINT(1) NOT NULL DEFAULT 0, 
    is_active TINYINT(1) NOT NULL DEFAULT 1, 
    is_ssl TINYINT(1) NOT NULL DEFAULT 1, 
    host VARCHAR(255) NOT NULL, 
    alias VARCHAR(100) NOT NULL DEFAULT '', 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    username VARCHAR(255) NOT NULL DEFAULT '', 
    password VARCHAR(255) NOT NULL DEFAULT '', 
    name VARCHAR(255) NOT NULL, 
    description TEXT
) engine=InnoDB;	

INSERT INTO internal_repos (is_ssl,host,name,description) VALUES (1, 'apex-platform.org', 'Apex Public Repository', 'The main, public repository for the Apex Software Platform.');

CREATE TABLE internal_packages (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    access ENUM('public','private') NOT NULL DEFAULT 'public', 
    repo_id INT NOT NULL, 
    version VARCHAR(15) NOT NULL DEFAULT '0.0.0', 
    prev_version VARCHAR(15) NOT NULL DEFAULT '0.0.0',
    date_installed TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    last_modified DATETIME,  
    alias VARCHAR(100) NOT NULL UNIQUE, 
    name VARCHAR(255) NOT NULL, 
    FOREIGN KEY (repo_id) REFERENCES internal_repos (id) ON DELETE CASCADE
) engine=InnoDB;
INSERT INTO internal_packages (repo_id, version, alias, name) VALUES (1, '1.0.0.0', 'core', 'Core Framework');


CREATE TABLE internal_components (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    order_num SMALLINT NOT NULL DEFAULT 0, 
    type VARCHAR(15) NOT NULL, 
    owner VARCHAR(100) NOT NULL, 
    package VARCHAR(100) NOT NULL, 
    parent VARCHAR(255) NOT NULL DEFAULT '', 
    alias VARCHAR(255) NOT NULL, 
    value TEXT NOT NULL, 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE, 
    FOREIGN KEY (owner) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE internal_crontab (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    autorun TINYINT(1) NOT NULL DEFAULT 1, 
    failed INT NOT NULL DEFAULT 0, 
    time_interval VARCHAR(10) NOT NULL, 
    nextrun_time INT NOT NULL DEFAULT 0, 
    lastrun_time INT NOT NULL DEFAULT 0, 
    package VARCHAR(100) NOT NULL, 
    alias VARCHAR(100) NOT NULL, 
    display_name VARCHAR(100) NOT NULL DEFAULT '', 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE internal_themes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    is_owner TINYINT(1) NOT NULL DEFAULT 0, 
    repo_id INT NOT NULL, 
    area VARCHAR(30) NOT NULL DEFAULT 'public', 
    alias VARCHAR(100) NOT NULL UNIQUE, 
    name VARCHAR(255) NOT NULL
) engine=InnoDB;

INSERT INTO internal_themes (repo_id,area,alias,name) VALUES (1, 'members', 'limitless', 'Limitless');
INSERT INTO internal_themes (repo_id,area,alias,name) VALUES (1, 'members', 'atlant_members', 'SuprAdmin - Member Area');
INSERT INTO internal_themes (repo_id,area,alias,name) VALUES (1, 'public', 'koupon', 'Koupon');

CREATE TABLE internal_boxlists (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    owner VARCHAR(100) NOT NULL, 
    package VARCHAR(100) NOT NULL, 
    alias VARCHAR(100) NOT NULL, 
    order_num SMALLINT NOT NULL, 
    href VARCHAR(100) NOT NULL, 
    title VARCHAR(100) NOT NULL, 
    description TEXT NOT NULL, 
	FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE, 
    FOREIGN KEY (owner) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE internal_languages (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    abbr VARCHAR(5) NOT NULL UNIQUE, 
    name VARCHAR(100) NOT NULL, 
    version VARCHAR(15) NOT NULL, 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) engine=InnoDB;

INSERT INTO internal_languages (abbr,name,version) VALUES ('en', 'English', '1.0.0');

CREATE TABLE internal_translations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    language VARCHAR(5) NOT NULL, 
    type ENUM('admin','members','public','system') NOT NULL DEFAULT 'system', 
    md5hash VARCHAR(100) NOT NULL, 
    contents TEXT NOT NULL
) engine=InnoDB DEFAULT CHARACTER SET=utf8;


CREATE TABLE internal_file_hashes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    upgrade_id INT NOT NULL DEFAULT 0,  
    filename VARCHAR(2550) NOT NULL, 
    file_hash VARCHAR(50) NOT NULL
) engine=InnoDB;

CREATE TABLE internal_upgrades (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    status ENUM('open','published','installed') NOT NULL DEFAULT 'installed', 
    package VARCHAR(100) NOT NULL, 
    version VARCHAR(15) NOT NULL,
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE internal_backups (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    filename VARCHAR(50) NOT NULL, 
    expire_date TIMESTAMP NOT NULL
) engine=InnoDB;


--------------------------------------------------
-- CMS 
--------------------------------------------------

CREATE TABLE cms_pages (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    area VARCHAR(100) NOT NULL DEFAULT 'public', 
    layout VARCHAR(255) NOT NULL DEFAULT 'default', 
    title VARCHAR(255) NOT NULL DEFAULT '', 
    filename VARCHAR(255) NOT NULL
) engine=InnoDB;

CREATE TABLE cms_menus ( 
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    package VARCHAR(100) NOT NULL, 
    area VARCHAR(50) NOT NULL DEFAULT 'members', 
    is_active TINYINT(1) NOT NULL DEFAULT 1, 
    is_system TINYINT(1) NOT NULL DEFAULT 1, 
    require_login TINYINT(1) NOT NULL DEFAULT 0, 
    require_nologin TINYINT(1) NOT NULL DEFAULT 0, 
    order_num SMALLINT NOT NULL DEFAULT 0, 
    link_type ENUM('internal','external','parent','header') NOT NULL DEFAULT 'internal', 
    icon VARCHAR(100) NOT NULL DEFAULT '', 
    parent VARCHAR(100) NOT NULL DEFAULT '', 
    alias VARCHAR (100) NOT NULL, 
    name VARCHAR(100) NOT NULL, 
    url VARCHAR(255) NOT NULL DEFAULT '' 
) engine=InnoDB;

CREATE TABLE cms_placeholders (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    package VARCHAR(100) NOT NULL, 
    uri VARCHAR(150) NOT NULL, 
    alias VARCHAR(150) NOT NULL, 
    contents TEXT NOT NULL, 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

--------------------------------------------------
-- Admin tables
--------------------------------------------------

CREATE TABLE admin (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    language VARCHAR(5) NOT NULL DEFAULT 'en', 
    timezone VARCHAR(5) NOT NULL DEFAULT 'EST',  
    require_2fa TINYINT(1) NOT NULL DEFAULT 0, 
    require_2fa_phone TINYINT(1) NOT NULL DEFAULT 0, 
    invalid_logins INT NOT NULL DEFAULT 0, 
    last_seen INT NOT NULL DEFAULT 0, 
    sec_hash VARCHAR(130) NOT NULL DEFAULT '', 
    date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,      
    username VARCHAR(100) NOT NULL UNIQUE, 
    password VARCHAR(130) NOT NULL DEFAULT '', 
    full_name VARCHAR(100) NOT NULL DEFAULT '', 
    email VARCHAR(100) NOT NULL DEFAULT '', 
    phone_country VARCHAR(5) NOT NULL DEFAULT'', 
    phone VARCHAR(30) NOT NULL DEFAULT ''
) engine=InnoDB;


--------------------------------------------------
-- Auth tables
--------------------------------------------------

CREATE TABLE auth_allowips ( 
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type ENUM('user','admin') NOT NULL DEFAULT 'user', 
    userid INT NOT NULL, 
    ip_address VARCHAR(45) NOT NULL
) engine=InnoDB;

CREATE TABLE auth_history (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type ENUM('user','admin') NOT NULL DEFAULT 'user', 
    userid INT NOT NULL, 
    ip_address VARCHAR(60) NOT NULL, 
    user_agent VARCHAR(150) NOT NULL, 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    logout_date DATETIME NOT NULL
) engine=InnoDB;

CREATE TABLE auth_history_pages (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    history_id INT NOT NULL, 
    request_method ENUM('GET','POST') NOT NULL, 
    uri VARCHAR(50) NOT NULL, 
    get_vars TEXT NOT NULL, 
    post_vars TEXT NOT NULL, 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (history_id) REFERENCES auth_history (id) ON DELETE CASCADE
) engine=InnoDB;


--------------------------------------------------
-- Encrypt tables
--------------------------------------------------

CREATE TABLE encrypt_keys (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type VARCHAR(20) NOT NULL DEFAULT 'user', 
    userid INT NOT NULL, 
    iv VARCHAR(30) NOT NULL,
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  
    public_key TEXT NOT NULL, 
    private_key TEXT NOT NULL
) engine=InnoDB;

CREATE TABLE encrypt_pgp_keys (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type VARCHAR(20) NOT NULL DEFAULT 'user', 
    userid INT NOT NULL, 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    fingerprint VARCHAR(150) NOT NULL DEFAULT '', 
    pgp_key TEXT NOT NULL
) engine=InnoDB;

CREATE TABLE encrypt_data (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    data LONGTEXT NOT NULL
) engine=InnoDB;

CREATE TABLE encrypt_data_keys (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    data_id INT NOT NULL,  
    key_id INT NOT NULL, 
    keydata TEXT NOT NULL, 
    FOREIGN KEY (data_id) REFERENCES encrypt_data (id) ON DELETE CASCADE, 
    FOREIGN KEY (key_id) REFERENCES encrypt_keys (id) ON DELETE CASCADE
) engine=InnoDB;


--------------------------------------------------
-- Notifications
--------------------------------------------------

CREATE TABLE notifications (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    is_active TINYINT(1) NOT NULL DEFAULT 1, 
    controller VARCHAR(100) NOT NULL, 
    sender VARCHAR(30) NOT NULL, 
    recipient VARCHAR(30) NOT NULL, 
    reply_to VARCHAR(100) NOT NULL DEFAULT '',  
    cc VARCHAR(100) NOT NULL DEFAULT '', 
    bcc VARCHAR(100) NOT NULL DEFAULT '',  
    content_type VARCHAR(30) NOT NULL DEFAULT 'text/plain', 
    subject VARCHAR(255) NOT NULL,
    contents LONGTEXT NOT NULL, 
    condition_vars TEXT NOT NULL
) Engine=InnoDB;

CREATE TABLE notifications_attachments ( 
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    notification_id INT NOT NULL, 
    mime_type VARCHAR(100) NOT NULL, 
    filename VARCHAR(255) NOT NULL, 
    contents LONGTEXT NOT NULL, 
    FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE notifications_queue (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    retries SMALLINT NOT NULL DEFAULT 0, 
    retry_time INT NOT NULL, 
    to_email VARCHAR(100) NOT NULL, 
    to_name VARCHAR(100) NOT NULL, 
    from_email VARCHAR(100) NOT NULL, 
    from_name VARCHAR(100) NOT NULL, 
    cc VARCHAR(255) NOT NULL DEFAULT '', 
    bcc VARCHAR(255) NOT NULL DEFAULT '', 
    content_type VARCHAR(30) NOT NULL DEFAULT 'text/plain', 
    has_attachments TINYINT(1) NOT NULL DEFAULT 0, 
    subject VARCHAR(255) NOT NULL, 
    message LONGTEXT NOT NULL
) engine=InnoDB;

CREATE TABLE notifications_mass_queue (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type ENUM('email','sms') NOT NULL DEFAULT 'email', 
    controller VARCHAR(50) NOT NULL DEFAULT 'users', 
    status ENUM('pending','in_progress','complete') NOT NULL DEFAULT 'pending', 
    total_sent INT NOT NULL DEFAULT 0, 
    send_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    from_name VARCHAR(100) NOT NULL, 
    from_email VARCHAR(100) NOT NULL, 
    reply_to VARCHAR(100) NOT NULL, 
    subject VARCHAR(255) NOT NULL, 
    message LONGTEXT NOT NULL, 
    condition_vars TEXT NOT NULL
) engine=InnoDB;

CREATE TABLE notifications_login_notices (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    require_agree TINYINT(1) NOT NULL DEFAULT 0, 
    type ENUM('modal','full') NOT NULL DEFAULT 'modal', 
    title VARCHAR(255) NOT NULL, 
    condition_vars TEXT NOT NULL, 
    message LONGTEXT NOT NULL
) engine=InnoDB;





--------------------------------------------------
-- Miscellaneous
--------------------------------------------------

CREATE TABLE images (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    type VARCHAR(50) NOT NULL, 
    record_id VARCHAR(50) NOT NULL DEFAULT '', 
    is_default TINYINT(1) NOT NULL DEFAULT 0, 
    size VARCHAR(15) NOT NULL DEFAULT 'full', 
    width INT NOT NULL DEFAULT 0, 
    height INT NOT NULL DEFAULT 0, 
    mime_type VARCHAR(50) NOT NULL DEFAULT 'image/jpg', 
    filename VARCHAR(100) NOT NULL
) engine = InnoDB;

CREATE TABLE images_contents (
    id INT NOT NULL PRIMARY KEY, 
    contents LONGBLOB NOT NULL, 
    FOREIGN KEY (id) REFERENCES images (id) ON DELETE CASCADE
) engine=InnoDB;


--------------------------------------------------
-- Dashboards
--------------------------------------------------

CREATE TABLE dashboard_items (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    package VARCHAR(100) NOT NULL, 
    area VARCHAR(30) NOT NULL DEFAULT 'admin', 
    type ENUM('top','right','tab') NOT NULL, 
    divid VARCHAR(100) NOT NULL DEFAULT '', 
    panel_class VARCHAR(100) NOT NULL DEFAULT '', 
    alias VARCHAR(255) NOT NULL, 
    title VARCHAR(255) NOT NULL, 
    description TEXT NOT NULL, 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE
) engine=InnoDB;

CREATE TABLE dashboard_profiles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    is_default TINYINT(1) NOT NULL DEFAULT 0, 
    area VARCHAR(30) NOT NULL, 
    userid INT NOT NULL
) engine=InnoDB;

INSERT INTO dashboard_profiles VALUES (1, 1, 'admin', 0);
INSERT INTO dashboard_profiles VALUES (2, 1, 'members', 0);

CREATE TABLE dashboard_profiles_items (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    profile_id INT NOT NULL, 
    type ENUM('top','right','tab') NOT NULL, 
    package VARCHAR(100) NOT NULL, 
    alias VARCHAR(255) NOT NULL, 
    FOREIGN KEY (profile_id) REFERENCES dashboard_profiles (id) ON DELETE CASCADE, 
    FOREIGN KEY (package) REFERENCES internal_packages (alias) ON DELETE CASCADE 
) engine=InnoDB;


