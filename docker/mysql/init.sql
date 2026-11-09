-- Base de datos separada para los tests (RefreshDatabase la borra y recrea en cada corrida)
CREATE DATABASE IF NOT EXISTS construckit_test;
GRANT ALL PRIVILEGES ON construckit_test.* TO 'construckit'@'%';