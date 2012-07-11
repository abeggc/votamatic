<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

require("../session.php");

if(initializeSessionFromShib()) {
	// A présent la session php est initialisée, avec toutes les infos requises
	// On peut donc rediriger l'utilisateur sur la page d'accueil
	if(!isset($_SESSION["cible"])) {
		$_SESSION["cible"] = preg_replace("/auth$/", "", dirname($_SERVER['PHP_SELF']));
	}
	if(strlen($_SESSION["cible"]) == 0 || substr($_SESSION["cible"], 0, 1) != "/")
		$_SESSION["cible"] = "/";
		
	header('Location: '.$_SESSION["cible"]);
} else {
	// Erreur
	print "Erreur Shibboleth";
	exit();
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="refresh" content="0;<?php print $_SESSION["cible"]; ?>" />
		<title>Votamatic</title>
	</head>
	<body>
	…
	</body>
</html>
<?php unset($_SESSION["cible"]); ?>