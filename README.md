# Media
A home made local media centre.

This web based media centre solution is build for storing simple movies and pictures.

## Technologies used
This piece of software makes use of an SQLite database and PHP as a server-side language.  
This project makes also use of an PHP routing solution which can be found at [simplePHPRouter](https://github.com/steampixel/simplePHPRouter).  
It also makes of an JWT implementation which can be found at [php-jwt](https://github.com/firebase/php-jwt).

## Installation
In order to make this system to work properly it should be connected to a SQLite database named media.db in the back-end folder of this project.
It should already contain the provided schema as depicted in the schema.sql file in the root of this project.

Define the file `constants.php` in the folder `back-end` and put in here the `JWT_SECRET` constant.

## Roadmap
* Simple user-interface.
* Account authorization.
* Movie playback and subtitle support.
* Display of pictures. 
