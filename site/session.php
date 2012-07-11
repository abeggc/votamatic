<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/
 
 // Durée maximale d'une session en secondes
 define("DUREE_MAX_SESSION", 4*3600);
 
 // Durée d'inactivé avant de détruire la session en secondes
 define("DUREE_INACTIVITE", 1800);
 
 // Les étudiants ne doivent pas se loguer avec shibboleth
 // true : pas d'auth shibboleth nécessaire pour participer
 // false: auth shibboleth requise pour tout le monde
 define("NO_SHIB_POUR_ETUDIANTS", true);
 
 // Mode debug sur localhost (pour auth utilisateur sans shib)
 // /!\ Ne pas mettre à true sur un serveur en production /!\
 define("VOTAMATIC_DEBUG", false);
 
 /**
  * Vérification de la session de l'utilisateur
  * S'il n'est pas dans la bdd et qu'il s'agit d'un prof, on lui crée un userid
  * L'identifiant unique de shibboleth est utilisé comme identifiant unique
  * de l'utilisateur.
  * Seuls les profs ont une session php, pas les étudiants.
  */
 function checkSession() {
 	
 	session_name("votamatic");
 	session_start();
 	
 	if(!isset($_SESSION["shib_uniqueID"]) || isExpired()) {
 		
 		// Session non initialisée ou expirée,
 		// Si la session expirée était authentifié avec shibboleth,
 		// on force l'authentification Shibboleth dans tous les cas
 		// Cela évite au prof de recliquer sur le lien de login. 		
 		if(NO_SHIB_POUR_ETUDIANTS && (!isset($_SESSION["authShib"]) || !$_SESSION["authShib"])) {
 			initializeSessionWithoutShib();
 			return;
 		} 		
 		
 		// Auth Shibboleth requise, on redirige sur auth/
 		$_SESSION["cible"] = $_SERVER["REQUEST_URI"];
 		if(dirname($_SERVER['PHP_SELF']) == "/")
 			header('Location: /auth/');
 		else
 			header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/auth/');
 		exit();
 	}
 	
 	$_SESSION["lastActivity"] = time();
 	define("VOTAMATIC_USERID", $_SESSION["shib_uniqueID"]);
 	
 	if(!isset($_SESSION["userid"])) {
 		// Récupération ou création d'un compte s'il s'agit d'un prof
 		$_SESSION["userid"] = getUserFromDB();
	}
	
	//Debug en local
 	if(VOTAMATIC_DEBUG && isset($_SESSION["debugUserid"])) {
 		$_SESSION["userid"] = getUserFromDB($_SESSION["debugUserid"]);
 		unset($_SESSION["debugUserid"]);
 	}

 }
 
 /**
  * Vérification si la session est expirée
  */
 function isExpired() {
 	if(time()-$_SESSION["lastActivity"] > DUREE_INACTIVITE
 		||  time()-$_SESSION["creationTime"] > DUREE_MAX_SESSION) {
 		$authShib = $_SESSION["authShib"];
 		session_destroy();
 		session_start();
 		if($authShib) {
 			$_SESSION["authShib"] = true;
 		}
 		return true;
 	} else 
 		return false;
 }
 
 /**
  * Récupère l'id de l'utilisateur de la base de données.
  * Si c'est sa première connexion, on vérifie avec les infos de shibboleth
  * et si c'est ok, on l'insère dans la bdd.
  */
 function getUserFromDB($uid=0) {
 	global $bd;
 	global $sql_table_utilisateurs;
 	
 	if($uid > 0) {
 		$stmt = $bd->prepare("SELECT u_id, role, nom, prenom " .
			"FROM $sql_table_utilisateurs " .
			"WHERE u_id = ?");
 	} else {
 		$stmt = $bd->prepare("SELECT u_id, role, nom, prenom " .
			"FROM $sql_table_utilisateurs " .
			"WHERE uniqueid = ?");
 	}

	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, " .
				"table $sql_table_utilisateurs"); 
	}

	if($uid > 0) {
		$stmt->bind_param('s', $uid);
	} else {
		$stmt->bind_param('s', $_SESSION["shib_uniqueID"]);
	}
	

	$stmt->execute();
	$stmt->store_result();
	$s_id = -1;
	$stmt->bind_result($_uid, $_role, $_nom, $_prenom);
	$stmt->fetch();
	if($stmt->num_rows == 1) {
		// L'utilisateur existe déjà
		$_SESSION["userid"] = $_uid;
		$_SESSION["role"] = $_role;
		$_SESSION["nom"] = $_nom;
		$_SESSION["prenom"] = $_prenom;
		$stmt->free_result();
		$stmt->close();
		return $_uid;
	} else {
		$stmt->close();
		// L'utilisateur n'existe pas
		// Peut-il avoir un compte ?
		if($uid == 0 && $_SESSION["isStaff"]) {
			return createUser();
		}
	}
	return -1;
 }
 
 /**
  * Vérification si l'utilisateur fait partie du staff
  */
 function checkIfIsStaff() {
 	if(!isset($_SERVER["affiliation"]))
 		return false;
 		
 	if (!isset($_SERVER["homeOrganization"]))
 		return false;
 		
 	if ($_SERVER["homeOrganization"] == "unige.ch") {
 		// staff si seulement les profs.
 		if( !(strpos($_SERVER["affiliation"], "staff") === false))
			return true;
 		if( !(strpos($_SERVER["affiliation"], "faculty") === false))
			return true;
 	}
 	
 	if ($_SERVER["homeOrganization"] == "hcuge.ch") {
 		return !(strpos($_SERVER["affiliation"], "member") === false);
 	}

 	return false;
 }
 
 /**
  * Insertion d'un nouvel utilisateur dans la bdd
  * à partir des infos reçues par Shibboleth
  */
 function createUser() {
 	global $bd;
 	global $sql_table_utilisateurs;
 	
 	$stmt = $bd->prepare("INSERT INTO $sql_table_utilisateurs " .
 		"SET u_id=NULL, role=0, uniqueid=?, nom=?, prenom=?, " .
 		"email=?, date_inscription=NOW()");

	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, " .
				"table $sql_table_utilisateurs"); 
	}

	$stmt->bind_param('ssss', $_SESSION["shib_uniqueID"], $_SESSION["shib_surname"], 
								$_SESSION["shib_givenName"], $_SESSION["shib_mail"]);

	$stmt->execute();
	if($bd->errno > 0) {
		throw new Exception("Erreur ".$bd->errno.", ".$bd->error);
	}
	$userid = $bd->insert_id;
	$stmt->close();
	
	return $userid;
 }
 
 /**
  * Retourne l'identifiant unique de l'utilisateur
  */
 function getUniqueID() {
	if(!isset($_SERVER["Shib-Session-ID"])) {
		return uniqid('', true); // Identifiant unique généré par php
	} else {
		return $_SERVER["uniqueID"];
 	}
 }
 
 
 /**
  * Initialisation d'une session votamatic, sans utiliser Shibboleth.
  * Cela est surtout utile pour les étudiants, leur permettant ainsi 
  * de participer aux sondages, sans login.
  */
 function initializeSessionWithoutShib() {
 	$_SESSION["lastActivity"] = time();
	$_SESSION["creationTime"] = time();
	$_SESSION["shib_uniqueID"] = getUniqueID();
	$_SESSION["isStaff"] = false;
	$_SESSION["authShib"] = false;
 }
 
 /**
  * Initialisation d'une session votamatic, à partir des infos Shibboleth
  * Cette fonction est uniquement appelée depuis le sous-répertoire "auth" qui 
  * est protégé par Shibboleth. Cela permet de se passer de Shibboleth pour 
  * l'utilisation de Votamatic.
  */
 function initializeSessionFromShib() {
 	session_name("votamatic");
	session_start();
	
	$_SESSION["lastActivity"] = time();
	$_SESSION["creationTime"] = time();
	
	//Copie des données nécessaire des variables Shibboleth
	$_SESSION["shib_uniqueID"] = getUniqueID();
	
	// Debug en local
	if(VOTAMATIC_DEBUG && isset($_GET["setuid"]) && $_GET["setuid"] > 0 && 
	   !isset($_SERVER["Shib-Session-ID"])) {
		$_SESSION["debugUserid"] = $_GET["setuid"];
		$_SESSION["authShib"] = false;
		return true;
	}
	
	// Copie des détails uniquement si nécessaire
	$_SESSION["isStaff"] = checkIfIsStaff();
	if($_SESSION["isStaff"]) {
		if(get_magic_quotes_gpc()) {
			$_SESSION["shib_surname"] = stripslashes($_SERVER["surname"]);
			$_SESSION["shib_givenName"] = stripslashes($_SERVER["givenName"]);
		} else {
			$_SESSION["shib_surname"] = $_SERVER["surname"];
			$_SESSION["shib_givenName"] = $_SERVER["givenName"];
		}
		$_SESSION["shib_mail"] = $_SERVER["mail"];
		//$_SESSION["shib_affiliation"] = $_SERVER["affiliation"];
		//$_SESSION["shib_homeOrganization"] = $_SERVER["homeOrganization"];
	}
	
	$_SESSION["authShib"] = isset($_SESSION["shib_uniqueID"]);
	unset($_SESSION["userid"]);
	
	return true;	
 }
 
?>
