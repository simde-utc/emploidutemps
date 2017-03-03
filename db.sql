CREATE TABLE `couleurs` (
 `uv` char(5) COLLATE utf8_bin NOT NULL,
 `color` char(7) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `cours` (
 `login` char(16) COLLATE utf8_bin NOT NULL,
 `id` int(11) NOT NULL,
 `color` char(7) COLLATE utf8_bin DEFAULT NULL,
 `actuel` tinyint(1) NOT NULL DEFAULT '1',
 `echange` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`login`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `echanges` (
 `idEchange` int(11) NOT NULL AUTO_INCREMENT,
 `idUV` char(5) COLLATE utf8_bin NOT NULL,
 `pour` char(5) COLLATE utf8_bin NOT NULL,
 `active` tinyint(1) NOT NULL DEFAULT '1',
 PRIMARY KEY (`idEchange`),
 UNIQUE KEY `idUV` (`idUV`,`pour`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `envoies` (
 `login` char(16) COLLATE utf8_bin NOT NULL,
 `idEchange` int(11) NOT NULL,
 `date` datetime DEFAULT NULL,
 `disponible` tinyint(1) NOT NULL DEFAULT '1',
 `echange` tinyint(1) NOT NULL DEFAULT '0',
 `note` text COLLATE utf8_bin NOT NULL,
 PRIMARY KEY (`login`,`idEchange`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `etudiants` (
 `login` char(16) COLLATE utf8_bin NOT NULL,
 `mail` text COLLATE utf8_bin,
 `prenom` text COLLATE utf8_bin,
 `nom` text COLLATE utf8_bin,
 `semestre` char(8) COLLATE utf8_bin NOT NULL,
 `nbrUV` mediumint(9) NOT NULL,
 `uvs` text COLLATE utf8_bin NOT NULL,
 `nouveau` tinyint(1) NOT NULL DEFAULT '1',
 `desinscrit` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `jours` (
 `jour` date NOT NULL,
 `type` int(11) NOT NULL,
 `alternance` char(1) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
 `semaine` char(1) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
 `infos` text CHARACTER SET utf8 COLLATE utf8_bin,
 PRIMARY KEY (`jour`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `recues` (
 `login` char(16) COLLATE utf8_bin NOT NULL,
 `idEchange` int(11) NOT NULL,
 `date` datetime DEFAULT NULL,
 `disponible` tinyint(1) NOT NULL DEFAULT '1',
 `echange` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`login`,`idEchange`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin

CREATE TABLE `uvs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `uv` char(5) COLLATE utf8_bin NOT NULL,
 `type` char(1) COLLATE utf8_bin NOT NULL,
 `groupe` mediumint(9) NOT NULL,
 `jour` smallint(6) NOT NULL,
 `debut` char(5) COLLATE utf8_bin NOT NULL,
 `fin` char(5) COLLATE utf8_bin NOT NULL,
 `salle` char(6) COLLATE utf8_bin NOT NULL,
 `frequence` tinyint(4) NOT NULL,
 `semaine` char(1) COLLATE utf8_bin NOT NULL,
 `nbrEtu` int(11) NOT NULL DEFAULT '1',
 PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin
