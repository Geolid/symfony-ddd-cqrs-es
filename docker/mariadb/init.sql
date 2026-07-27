-- Three logical schemas for one physical MariaDB instance, matching the three
-- doctrine.dbal connections in config/packages/doctrine.php: the write-side event store, the
-- read-side projections, and Messenger's own transport table.
CREATE DATABASE IF NOT EXISTS app_event_store CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS app_read_model CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS app_messenger CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON app_event_store.* TO 'app'@'%';
GRANT ALL PRIVILEGES ON app_read_model.* TO 'app'@'%';
GRANT ALL PRIVILEGES ON app_messenger.* TO 'app'@'%';
FLUSH PRIVILEGES;
