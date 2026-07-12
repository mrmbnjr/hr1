CREATE DATABASE ramyum;

USE ramyum;

CREATE TABLE users(

id INT AUTO_INCREMENT PRIMARY KEY,

username VARCHAR(50) UNIQUE,

password VARCHAR(255)

);

UPDATE users
SET password = '$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'
WHERE username = 'admin';