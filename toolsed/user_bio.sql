USE TOOLSED;

#ALTER TABLE UF_USER_BIO MODIFY ID INTEGER;
#ALTER TABLE UF_USER_BIO MODIFY PHOTO VARCHAR(200);

#UPDATE UF_USER_BIO SET photo='profile_default.png';

#INSERT INTO UF_USER_BIO() SELECT ID FROM UF_USER_BIO
#UPDATE UF_USER_BIO SET photo=LOAD_FILE("C:\xampp\htdocs\home\userfrosting\profile_default.png") WHERE id=(SELECT id FROM UF_USER_BIO);

/*

CREATE TABLE TE_CIRCLE(
id INT NOT NULL AUTO_INCREMENT,
type INT NOT NULL DEFAULT '1',
photo VARCHAR(500) NOT NULL DEFAULT 'profile_default.png', 
cover VARCHAR(500) NOT NULL DEFAULT 'cover_default.png',
description VARCHAR(500) NOT NULL DEFAULT '',
PRIMARY KEY (id)
);
*/


CREATE TABLE TE_CIRCLE_USER(
id INT NOT NULL AUTO_INCREMENT,
user_id INT NOT NULL DEFAULT '1',
circle_id INT NOT NULL DEFAULT '1',
admin BOOLEAN NOT NULL DEFAULT '0',
PRIMARY KEY (id)
);
