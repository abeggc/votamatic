-- phpMyAdmin SQL Dump
-- version 3.4.0-dev
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Mer 25 Mai 2011 à 23:16
-- Version du serveur: 5.1.51
-- Version de PHP: 5.3.6-pl0-gentoo

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `votamatic`
--

-- --------------------------------------------------------

--
-- Structure de la table `votamatic_questions`
--

CREATE TABLE IF NOT EXISTS `votamatic_questions` (
  `q_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de la question',
  `s_id` int(10) unsigned NOT NULL COMMENT 'Identifiant du sondage auquel la question appartient',
  `type` tinyint(3) unsigned NOT NULL COMMENT 'Type de question (1 réponse, plusieurs réponses, etc.)',
  `texte` text NOT NULL COMMENT 'Le texte de la question',
  PRIMARY KEY (`q_id`),
  KEY `s_id` (`s_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Liste des questions';

-- --------------------------------------------------------

--
-- Structure de la table `votamatic_reponses`
--

CREATE TABLE IF NOT EXISTS `votamatic_reponses` (
  `r_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de la réponse',
  `q_id` int(10) unsigned NOT NULL COMMENT 'Identifiant de la question à laquelle correspond cette réponse',
  `texte` tinytext NOT NULL COMMENT 'Le texte de la réponse',
  `nb_voix` int(10) unsigned NOT NULL COMMENT 'Nombre de votants ayant choisis cette réponse',
  PRIMARY KEY (`r_id`),
  KEY `q_id` (`q_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Liste des réponses';

-- --------------------------------------------------------

--
-- Structure de la table `votamatic_sondages`
--

CREATE TABLE IF NOT EXISTS `votamatic_sondages` (
  `s_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique du sondage',
  `u_id` int(10) unsigned NOT NULL COMMENT 'Identifiant de l''utilisateur responsable du sondage',
  `texte` text NOT NULL COMMENT 'Description du sondage',
  `etat` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Etat du sondage (encours, pret, etc.)',
  `visibilite` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Visibilité des résultats',
  `duree` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Durée en secondes du sondage',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du sondage',
  `date_evaluation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Date de l''évalution du sondage',
  `nb_participants` int(10) unsigned NOT NULL COMMENT 'Nombre de participants ayant répondu au sondage',
  PRIMARY KEY (`s_id`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Liste des sondages';

-- --------------------------------------------------------

--
-- Structure de la table `votamatic_utilisateurs`
--

CREATE TABLE IF NOT EXISTS `votamatic_utilisateurs` (
  `u_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de l''utilisateur',
  `role` tinyint(4) NOT NULL COMMENT 'Rôle (admin, enseignant, etc.)',
  `uniqueid` varchar(255) NOT NULL COMMENT 'UniqueID reçu par Shibboleth',
  `nom` varchar(50) NOT NULL COMMENT 'Nom de l''utilisateur',
  `prenom` varchar(50) NOT NULL COMMENT 'Prénom de l''utilisateur',
  `email` varchar(100) NOT NULL COMMENT 'Adresse email',
  `date_inscription` datetime NOT NULL COMMENT 'Date de la première utilisation de l''application',
  PRIMARY KEY (`u_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Liste des utilisateurs pouvant créer et gérer les sondages';

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `votamatic_questions`
--
ALTER TABLE `votamatic_questions`
  ADD CONSTRAINT `votamatic_questions_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `votamatic_sondages` (`s_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `votamatic_reponses`
--
ALTER TABLE `votamatic_reponses`
  ADD CONSTRAINT `votamatic_reponses_ibfk_1` FOREIGN KEY (`q_id`) REFERENCES `votamatic_questions` (`q_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `votamatic_sondages`
--
ALTER TABLE `votamatic_sondages`
  ADD CONSTRAINT `votamatic_sondages_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `votamatic_utilisateurs` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE;
