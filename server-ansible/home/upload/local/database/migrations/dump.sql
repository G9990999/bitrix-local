-- ==========================================================
-- СТРУКТУРА (Нижний регистр для имен)
-- ==========================================================
CREATE TABLE IF NOT EXISTS b_sec_wwall_rules (
  id SERIAL PRIMARY KEY,
  data text,
  sort integer DEFAULT 100
);

CREATE TABLE IF NOT EXISTS b_language (
  id char(2) PRIMARY KEY, -- В D7 это ID, а не LID
  lid char(2) NOT NULL,
  sort integer NOT NULL DEFAULT 100,
  def char(1) NOT NULL DEFAULT 'N',
  active char(1) NOT NULL DEFAULT 'Y',
  name varchar(50) NOT NULL,
  code char(2), 
  culture_id integer
);

CREATE TABLE IF NOT EXISTS b_lang (
  lid char(2) PRIMARY KEY, -- А здесь LID
  sort integer DEFAULT 100,
  def char(1) NOT NULL DEFAULT 'N',
  active char(1) NOT NULL DEFAULT 'Y',
  name varchar(255) NOT NULL,
  dir varchar(255) NOT NULL,
  language_id char(2) NOT NULL,
  doc_root varchar(255),
  domain_limited char(1) NOT NULL DEFAULT 'N',
  server_name varchar(255),
  site_name varchar(255),
  email varchar(255),
  culture_id integer
);

CREATE TABLE IF NOT EXISTS b_lang_domain (
  lid char(2) NOT NULL,
  domain varchar(255) NOT NULL,
  PRIMARY KEY (lid, domain)
);

CREATE TABLE IF NOT EXISTS b_option (
  module_id varchar(50),
  name varchar(50) NOT NULL,
  value text,
  description varchar(255),
  site_id char(2)
);

CREATE TABLE IF NOT EXISTS b_module (
  id varchar(50) PRIMARY KEY,
  installed char(1) NOT NULL DEFAULT 'Y'
);

CREATE TABLE IF NOT EXISTS b_user (
  id SERIAL PRIMARY KEY,
  timestamp_x timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  login varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  active char(1) NOT NULL DEFAULT 'Y',
  email varchar(255),
  CONSTRAINT ux_user_login UNIQUE (login)
);

CREATE TABLE IF NOT EXISTS b_event_log (
  id SERIAL PRIMARY KEY,
  timestamp_x timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  event_id varchar(50) DEFAULT 'UNKNOWN',
  severity varchar(50) DEFAULT 'INFO',
  audit_type_id varchar(50) DEFAULT 'SYSTEM',
  module_id varchar(50) DEFAULT 'main',
  item_id varchar(255) DEFAULT '0',
  remote_addr varchar(40),
  user_agent text,
  request_uri text,
  forum_id integer,
  topic_id integer,
  message text,
  description text,
  user_id integer,
  site_id char(2)
);
-- ==========================================================
-- ДАННЫЕ (Значения в ВЕРХНЕМ регистре для Битрикса)
-- ==========================================================
CREATE TABLE IF NOT EXISTS b_culture (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  code varchar(50),
  format_date varchar(50) DEFAULT 'DD.MM.YYYY',
  format_datetime varchar(50) DEFAULT 'DD.MM.YYYY HH:MI:SS', -- ЭТО ПОЛЕ ОШИБКА И ИСКАЛА
  format_name varchar(255) DEFAULT '#NAME# #LAST_NAME#',
  week_start integer DEFAULT 1,
  charset varchar(50) DEFAULT 'UTF-8',
  direction char(1) DEFAULT 'Y',
  short_date_format varchar(50) DEFAULT 'DD.MM.YYYY',
  medium_date_format varchar(50) DEFAULT 'D MMM YYYY',
  long_date_format varchar(50) DEFAULT 'D MMMM YYYY',
  full_date_format varchar(50) DEFAULT 'LLLL',
  day_month_format varchar(50) DEFAULT 'D MMMM',
  day_short_month_format varchar(50) DEFAULT 'D MMM',
  day_of_week_month_format varchar(50) DEFAULT 'EE, D MMMM',
  short_day_of_week_month_format varchar(50) DEFAULT 'E, D MMMM',
  short_day_of_week_short_month_format varchar(50) DEFAULT 'E, D MMM',
  short_time_format varchar(50) DEFAULT 'HH:MI',
  long_time_format varchar(50) DEFAULT 'HH:MI:SS',
  am_value varchar(20),
  pm_value varchar(20),
  number_thousands_separator varchar(10) DEFAULT ' ',
  number_decimal_separator varchar(10) DEFAULT '.',
  number_decimals integer DEFAULT 2
);

-- Наполнение данными (ID=1 для России)
INSERT INTO b_culture (
  id, name, code, charset, 
  format_date, format_datetime, 
  short_date_format, medium_date_format, long_date_format, full_date_format,
  day_month_format, day_short_month_format, day_of_week_month_format,
  short_day_of_week_month_format, short_day_of_week_short_month_format,
  short_time_format, long_time_format,
  number_thousands_separator, number_decimal_separator, number_decimals
) VALUES (
  1, 'Russian', 'ru', 'UTF-8', 
  'DD.MM.YYYY', 'DD.MM.YYYY HH:MI:SS',
  'DD.MM.YYYY', 'D MMM YYYY', 'D MMMM YYYY', 'LLLL',
  'D MMMM', 'D MMM', 'EE, D MMMM',
  'E, D MMMM', 'E, D MMM',
  'HH:MI', 'HH:MI:SS',
  ' ', '.', 2
)
ON CONFLICT (id) DO NOTHING;

-- Наполнение данными (Добавляем ON CONFLICT, чтобы не было Error: duplicate key)
INSERT INTO b_culture (id, name, code, charset) 
VALUES (1, 'Russian', 'ru', 'UTF-8') 
ON CONFLICT (id) DO NOTHING;

INSERT INTO b_language (id, lid, sort, def, active, name, code, culture_id) 
VALUES ('ru', 'ru', 1, 'Y', 'Y', 'Russian', 'ru', 1) 
ON CONFLICT (id) DO NOTHING;

INSERT INTO b_lang (lid, sort, def, active, name, dir, language_id, culture_id) 
VALUES ('s1', 1, 'Y', 'Y', 'Main Site', '/', 'ru', 1) 
ON CONFLICT (lid) DO NOTHING;

INSERT INTO b_lang_domain (lid, domain) 
VALUES ('s1', 'localhost') 
ON CONFLICT (lid, domain) DO NOTHING;

INSERT INTO b_module (id, installed) 
VALUES ('main', 'Y') 
ON CONFLICT (id) DO NOTHING;

INSERT INTO b_user (id, login, password, active, email) 
VALUES (1, 'admin', 'ef71a4b270e4c6c09b8b0e8c68f8a846', 'Y', 'admin@example.com') 
ON CONFLICT (id) DO NOTHING;

-- Наполнение основными настройками ядра
-- Для b_option используем фильтр по ключу (так как там может не быть UNIQUE индекса сразу)
-- 1. Создаем правильный уникальный индекс, который ожидает логика Битрикса
CREATE UNIQUE INDEX IF NOT EXISTS ux_b_option_composite ON b_option (module_id, name, site_id);

-- 2. Выполняем Upsert (Вставка или обновление)
INSERT INTO b_option (module_id, name, value, site_id) VALUES 
  ('main', 'utf_mode', 'Y', NULL), 
  ('main', 'update_devsrv', 'Y', NULL),
  ('main', 'install_date', '1774828800', NULL),
  ('main', 'admin_passwordh', '1774828800', NULL),
  ('main', 'site_name', 'RoleModel Headless B24', NULL)
ON CONFLICT (module_id, name, site_id) 
DO UPDATE SET value = EXCLUDED.value;

-- ==========================================================
-- ВЕБХУКИ (REST EVENTS)
-- ==========================================================
-- Таблица для очереди вебхуков
CREATE TABLE IF NOT EXISTS rolemodel_webhook_queue (
    id SERIAL PRIMARY KEY,
    event_name varchar(100) NOT NULL,
    payload jsonb,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

-- 1. Убедимся, что таблица существует (минимальный набор полей для работы)
CREATE TABLE IF NOT EXISTS b_rest_event (
  id SERIAL PRIMARY KEY,
  event_name varchar(255) NOT NULL,
  handler varchar(255) NOT NULL,
  user_id int DEFAULT 1,
  application_id int DEFAULT 0,
  app_id varchar(128) DEFAULT NULL
);

-- 2. Создаем уникальный индекс для корректной работы ON CONFLICT
-- (Чтобы не плодить одинаковые хуки на одно и то же событие)
CREATE UNIQUE INDEX IF NOT EXISTS ux_b_rest_event_handler ON b_rest_event (event_name, handler);

-- 3. Наполнение данными
INSERT INTO b_rest_event (event_name, handler, user_id) VALUES 
  ('ONCRMDEALADD',    'http://127.0.0.1', 1),
  ('ONCRMDEALUPDATE', 'http://127.0.0.1', 1),
  ('ONCRMDEALDELETE', 'http://127.0.0.1', 1)
ON CONFLICT (event_name, handler) DO NOTHING;

-- Синхронизация счетчиков
SELECT setval(pg_get_serial_sequence('b_rest_event', 'id'), coalesce(max(id), 1)) FROM b_rest_event;
SELECT setval(pg_get_serial_sequence('b_culture', 'id'), coalesce(max(id), 1)) FROM b_culture;
SELECT setval(pg_get_serial_sequence('b_user', 'id'), coalesce(max(id), 1)) FROM b_user;
-- Ставим дату установки на 01.05.2026 (timestamp 1777593600)
