-- Stores information about a user.
CREATE TABLE users (
    username    TEXT UNIQUE,
    password    TEXT
);

-- Stores information about a picture.
-- If thumbnail is true, then it has to be linked to either a movie or category.
-- If thumbnail is false, the picture is used stand-alone, just like a video.
CREATE TABLE pictures (
	pid			INTEGER PRIMARY KEY AUTOINCREMENT ,
  	filepath 	TEXT NOT NULL,
  	thumbnail 	BOOL
);

-- Stores information about a movie.
CREATE TABLE movies (
	mid			INTEGER PRIMARY KEY AUTOINCREMENT ,
  	name 		TEXT,
  	filepath	TEXT NOT NULL,
    image		INTEGER REFERENCES pictures(pid),
  	summary		TEXT
);

-- Stores information about a category.
CREATE TABLE categories (
	cid		INTEGER PRIMARY KEY AUTOINCREMENT ,
  	name	TEXT NOT NULL,
  	image	INTEGER REFERENCES pictures(pid)
);

-- Stores information about the access to a certain category for a certain user.
CREATE TABLE access (
    username    TEXT REFERENCES users(username) ON DELETE CASCADE ,
    cid         INTEGER REFERENCES categories(cid) ON DELETE CASCADE
);

-- Stores information about a subtitle.
CREATE TABLE subtitles (
	sid			INTEGER PRIMARY KEY AUTOINCREMENT ,
  	mid			INTEGER REFERENCES movies(mid) ON DELETE CASCADE NOT NULL,
  	language	TEXT NOT NULL,
  	filepath	TEXT NOT NULL
);

-- Connects a movie to one or more categories.
CREATE TABLE movie_category (
	mid         INTEGER REFERENCES movies(mid) ON DELETE CASCADE ,
  	cid         INTEGER REFERENCES categories(cid) ON DELETE CASCADE
);

-- Connects a picture to one or more categories.
CREATE TABLE picture_category (
	pid         INTEGER REFERENCES pictures(pid) ON DELETE CASCADE ,
  	cid         INTEGER REFERENCES categories(cid) ON DELETE CASCADE
);