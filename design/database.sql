DROP DATABASE IF EXISTS movies;
CREATE DATABASE `movies` DEFAULT CHARACTER SET utf8 COLLATE utf8_swedish_ci;
USE movies;
CREATE TABLE studio
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        studio CHAR(255) NOT NULL,
        PRIMARY KEY(id)
) ENGINE = InnoDB;
CREATE INDEX studio ON studio(studio);
CREATE TABLE production
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        type BOOL,
        title CHAR(255),
        plot TEXT,
        rating FLOAT NOT NULL,
        votes INT UNSIGNED NOT NULL,
        PRIMARY KEY(id)
) ENGINE = InnoDB; 
CREATE INDEX productionRating ON production(rating);


CREATE TABLE country
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        country CHAR(255) NOT NULL,
        PRIMARY KEY(id)
) ENGINE = InnoDB;
CREATE INDEX country ON country(country); 

CREATE TABLE originaltitles
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        idProduction INT UNSIGNED NOT NULL,
        title CHAR(255),
        PRIMARY KEY (id),
        CONSTRAINT FKoriginaltitlesProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE
) ENGINE = InnoDB;
CREATE INDEX originaltitlesTitle ON originaltitles(title);
CREATE INDEX originaltitlesProduction ON originaltitles(idProduction); 
/*
SELECT originaltitles.title, country.id, country.country
FROM originaltitles
JOIN countrytitles ON countrytitles.idOriginaltitle = originaltitles.id
JOIN country ON country.id = countrytitles.idCountry
WHERE originaltitles.idProduction =740
GROUP BY country.id
*/
CREATE TABLE countrytitles
(
        idOriginaltitle INT UNSIGNED NOT NULL,
        idCountry INT UNSIGNED NOT NULL,
        PRIMARY KEY(idOriginaltitle,idCountry),
        CONSTRAINT FKcountrytitlesOriginaltitle FOREIGN KEY (idOriginaltitle) REFERENCES originaltitles(id) ON DELETE CASCADE,
        CONSTRAINT FKoriginaltitlesCountry FOREIGN KEY (idCountry) REFERENCES country(id) ON DELETE CASCADE
) ENGINE = InnoDB;
CREATE INDEX countrytitlesCountry ON countrytitles(idCountry);
CREATE INDEX countrytitlesOriginaltitle ON countrytitles(idOriginaltitle);

CREATE TABLE keyword
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword CHAR(100),
        PRIMARY KEY (id)
) ENGINE = InnoDB;
CREATE INDEX keyword ON keyword(keyword);

CREATE TABLE keywordproduction
(
        idKeyword INT UNSIGNED NOT NULL,
        idProduction INT UNSIGNED NOT NULL,
        PRIMARY KEY(idKeyword,idProduction),
        CONSTRAINT FKkeywordProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
        CONSTRAINT FKkeywordKeyword FOREIGN KEY (idKeyword) REFERENCES keyword(id) ON DELETE CASCADE
) ENGINE = InnoDB;
CREATE INDEX keywordproductionKeyword ON keywordproduction(idKeyword);
CREATE INDEX keywordproductionProduction ON keywordproduction(idProduction);

CREATE TABLE movie
(
        idProduction INT UNSIGNED NOT NULL,
        idStudio INT UNSIGNED NOT NULL, 
        imdb INT(8) UNSIGNED NOT NULL,  
        top250 INT NOT NULL,
        year SMALLINT(4) UNSIGNED NOT NULL,
        outline TINYTEXT NOT NULL,  
        tagline TINYTEXT NOT NULL,  
        mpaa TINYTEXT NOT NULL,
        runtime INT UNSIGNED NOT NULL,
        PRIMARY KEY (idProduction), 
        UNIQUE (imdb),
        CONSTRAINT FKmovieProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
        CONSTRAINT FKproductionStudio FOREIGN KEY (idStudio) REFERENCES studio(id) 
) ENGINE = InnoDB;
CREATE INDEX movieStudio ON movie(idStudio);
CREATE INDEX movieYear ON movie(year);

CREATE TABLE file
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT, 
        idProduction INT UNSIGNED NOT NULL,
        playcount INT UNSIGNED NOT NULL,
        path VARCHAR(300),
        filename VARCHAR(270),
        filesize DOUBLE,
        duration INT,
        format CHAR(30),
        width INT,
        height INT,
        ar CHAR(10),
        writinglibrary CHAR(50),
        videobitrate DOUBLE,
		hassubtitle INT(1),
		timeAdded DATETIME,
        PRIMARY KEY (id),
        CONSTRAINT FKfileProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE
) ENGINE = InnoDB;
CREATE INDEX fileProduction ON file(idProduction);
CREATE INDEX filePath ON file(path);
CREATE INDEX fileFilename ON file(filename);
CREATE INDEX fileWidth ON file(width);
CREATE INDEX fileHeight ON file(height);

CREATE TABLE audiotrack
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT, 
        idFile INT UNSIGNED NOT NULL,
	format CHAR(10),
        formatinfo CHAR(50),
        channels INT,
        bitrate INT,
	title CHAR(255),
	language CHAR(30),
        PRIMARY KEY (id),
	CONSTRAINT FKaudiotrackFile FOREIGN KEY (idFile) REFERENCES file(id) ON DELETE CASCADE
) ENGINE = InnoDB;     
CREATE INDEX audiotrackLanguage ON audiotrack(language); 
 
CREATE TABLE tvshow
(
        idProduction INT UNSIGNED NOT NULL,
        premiered DATE,
        imdb INT(8) UNSIGNED NOT NULL, 
        PRIMARY KEY (idProduction),
        CONSTRAINT FKtvshowProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE
) ENGINE = InnoDB; 

CREATE INDEX tvshowImdb ON tvshow(imdb); 

CREATE TABLE photo
(            
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    path CHAR(200),
    idProduction INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT FKphotoProduction FOREIGN KEY (idProduction) REFERENCES production(id)  ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX photoProduction ON photo(idProduction); 

CREATE TABLE fanart
(            
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    path CHAR(100),
    idProduction INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT FKfanartProduction FOREIGN KEY (idProduction) REFERENCES production(id)  ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX fanartPath ON fanart(path);
CREATE INDEX fanartProduction ON fanart(idProduction);
 
CREATE TABLE previewfanart
(            
    idFanart INT UNSIGNED NOT NULL,
    path CHAR(100),
    PRIMARY KEY (idFanart),
    CONSTRAINT FKpreviewFanart FOREIGN KEY (idFanart) REFERENCES fanart(id)  ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX previewfanartPath ON previewfanart(path);

CREATE TABLE person
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name CHAR(100),
		bio TINYTEXT NOT NULL,
		dob DATE NOT NULL,
		birthplace CHAR(100),
		gender INT(1) DEFAULT 0,
        PRIMARY KEY(id)
) ENGINE = InnoDB;
CREATE INDEX person ON person(name);

CREATE TABLE personphoto
(            
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    path CHAR(150),
    idPerson INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT FKpersonPhotoPerson FOREIGN KEY (idPerson) REFERENCES person(id)  ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX personphotoPerson ON personphoto(idPerson); 
CREATE INDEX personphotoPath ON personphoto(path); 

CREATE TABLE acting
(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role CHAR(120) NOT NULL,
    idProduction INT UNSIGNED NOT NULL,
    idPerson INT UNSIGNED NOT NULL,
    PRIMARY KEY ( role,idProduction,idPerson ),
    UNIQUE KEY(id),
    CONSTRAINT FKactingProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
    CONSTRAINT FKactingPerson FOREIGN KEY (idPerson) REFERENCES person(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX actingProduction ON acting(idProduction);
CREATE INDEX actingPerson ON acting(idPerson);
CREATE INDEX actingRole ON acting(role); 

CREATE TABLE writing
(
    idProduction INT UNSIGNED NOT NULL,
    idPerson INT UNSIGNED NOT NULL,
    PRIMARY KEY ( `idProduction` , `idPerson` ),
    CONSTRAINT FKwritingProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
    CONSTRAINT FKwritingPerson FOREIGN KEY (idPerson) REFERENCES person(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX writingProduction ON writing(idProduction);
CREATE INDEX writingPerson ON writing(idPerson);

CREATE TABLE directing
(
    idProduction INT UNSIGNED NOT NULL,
    idPerson INT UNSIGNED NOT NULL,
    PRIMARY KEY ( `idProduction` , `idPerson` ),
    CONSTRAINT FKdirectingProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
    CONSTRAINT FKdirectingPerson FOREIGN KEY (idPerson) REFERENCES person(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX directingProduction ON directing(idProduction);
CREATE INDEX directingPerson ON directing(idPerson);

CREATE TABLE episode 
(  
        idProduction INT UNSIGNED NOT NULL,
        idTvshow INT UNSIGNED NOT NULL,   
        season INT UNSIGNED NOT NULL,
        episode INT UNSIGNED NOT NULL,
        mpaa TINYTEXT NOT NULL,
        aired DATE,
        runtime INT UNSIGNED NOT NULL,
        imdb INT(8) UNSIGNED NOT NULL, 
        PRIMARY KEY ( idProduction ),
        CONSTRAINT FKepisodeProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
        CONSTRAINT FKepisodeTvshow FOREIGN KEY (idTvshow) REFERENCES production(id) ON DELETE CASCADE   
) ENGINE = InnoDB; 
CREATE INDEX episodeSeason ON episode(season);
CREATE INDEX episodeEpisode ON episode(episode);
CREATE INDEX episodeTvshow ON episode(idTvshow);
CREATE INDEX episodeImdb ON episode(imdb); 

CREATE TABLE genre
(
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        genre CHAR(20),
        PRIMARY KEY(id),
        UNIQUE(genre)
) ENGINE = InnoDB;
CREATE INDEX genreIndex ON genre(genre);

CREATE TABLE productiongenre
(
        idProduction INT UNSIGNED NOT NULL,
        idGenre INT UNSIGNED NOT NULL,
        PRIMARY KEY (idGenre,idProduction),
        CONSTRAINT FKproductiongenreProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE,
        CONSTRAINT FKproductiongenreGenre FOREIGN KEY (idGenre) REFERENCES genre(id) ON DELETE CASCADE 
 ) ENGINE = InnoDB;            
CREATE INDEX productiongenreProduction ON productiongenre(idProduction);
CREATE INDEX productiongenreGenre ON productiongenre(idGenre);   

CREATE TABLE rssitem
(
        link CHAR(200),
        hd BOOL,
        season INT UNSIGNED NOT NULL,
        episode INT UNSIGNED NOT NULL,
        date DATE,  
        showname CHAR(255),
        filename CHAR(255),
        PRIMARY KEY(link)
) ENGINE = InnoDB; 

CREATE TABLE rssmovie
(
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	idProduction INT UNSIGNED NOT NULL,
	link TINYTEXT,
	timeReleased DATETIME,  
	releaseName TINYTEXT,
	downloaded BOOL,
	fullHD BOOL,
	lastCheck DATETIME,
	nrOfChecks INT UNSIGNED NOT NULL,
	manuallyAdded BOOL,
	PRIMARY KEY(id),
	CONSTRAINT FKrssmovieProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX rssmovieProduction ON rssmovie(idProduction);

CREATE TABLE rssmovielinks
(
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	idProduction INT UNSIGNED NOT NULL,
	link TINYTEXT,
	PRIMARY KEY(id),
	CONSTRAINT FKrssmovielinkProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX rssmovielinksProduction ON rssmovielinks(idProduction);

CREATE TABLE failedrssmovielinks
(
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	link TINYTEXT,
	lastCheck DATETIME,
	PRIMARY KEY(id)
) ENGINE = InnoDB;
CREATE INDEX failedrssmovielinksDate ON failedrssmovielinks(lastCheck);

CREATE TABLE filesintorrents
(
	idProduction INT UNSIGNED NOT NULL,
	path VARCHAR(255),
	PRIMARY KEY(idProduction,path),
	CONSTRAINT FKfilesintorrentsProduction FOREIGN KEY (idProduction) REFERENCES production(id) ON DELETE CASCADE 
) ENGINE = InnoDB;
CREATE INDEX filesintorrentsPaths ON filesintorrents(path);