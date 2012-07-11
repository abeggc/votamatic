<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

//Gestion session
require("libs/mysql.php");
require("libs/sondage.class.php");
require("session.php");
$bd = connexionBD();
checkSession();
 if(!isset($_SESSION["role"]) || $_SESSION["role"] != 1) {
 	header('Location: index.php'); 
 	return;
 }
 	
 	
 if(isset($_GET['action'])) {
 	//Action reçue par GET -> del
 	$action = $_GET['action'];
 	$uid = -1;
 	if(isset($_GET['uid']) && is_numeric($_GET['uid']) && $_GET['uid'] > 0) {
 		$uid = $_GET['uid'];
 	}
 }
 
 // Protection contre l'effacement de soi-même
 if($uid > 0 && $uid == $_SESSION["userid"]) {
 	$uid = -1;
 }
 
 if($uid > 0) {
 	//Suppression de l'utilisateur
 	$stmt = $bd->prepare("DELETE FROM $sql_table_utilisateurs WHERE u_id = ?;");
 	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, table $sql_table_utilisateurs"); 
	}
			
	$stmt->bind_param('i', $uid);
	$stmt->execute();
 }
 	
$users = array();
global $sql_table_utilisateurs;
$sql = "SELECT u_id, nom, prenom, uniqueid, role FROM $sql_table_utilisateurs " .
		"ORDER BY nom ASC, prenom ASC;";
$result = $bd->query($sql);
while($row = $result->fetch_object()) {
 	$users[] = array($row->u_id, $row->nom, $row->prenom, $row->uniqueid, $row->role);
 }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="author" content="Christian Abegg" />
		<meta name="description" content="sondage" />
		<meta name="keywords" content="sondage, ntic, unige" />
		
		<title>Votamatic</title>
		<link rel="stylesheet"  href="jquery.mobile-1.0.1.min.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>
		<script type="text/javascript">
		 //<![CDATA[ 
		 

function conf(uid) {
	if(confirm("Etes-vous sûr de vouloir supprimer cet utilisateur ?")) {
		$(location).attr('href',"admin.php?action=del&uid="+uid);
	}
}

//]]> </script>

	</head>
	<body>
	<div data-role="page" class="type-home">
	<div data-role="header">
		<h1 id="titre">Administration des utilisateurs</h1>
		<a href="index.php" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-left jqm-home">Accueil</a>
	</div>
	  <div data-role="content">
		<div class="content-primary">
		<h3>Liste des enseignants pouvant créer des sondages</h3>
		<ul data-role="listview" data-filter="true" data-filter-placeholder="Recherche d'un enseignant" data-split-icon="delete" data-inset="true" data-theme="c" data-dividertheme="b">
			<?php
				if(sizeof($users) == 0) {
					echo "<li>Aucun enseignant</li>\n";
				}
				$curLetter = "";
				foreach($users as $u) {
					// Affichage d'une séparation pour chaque lettre
					if(substr($u[1], 0, 1) != $curLetter) {
						$curLetter = substr($u[1], 0, 1);
						echo "<li data-role=\"list-divider\">".$curLetter."</li>\n";
					}
					$domains = explode("@", $u[3]);
					if(sizeof($domains) > 1) {
						$domain = $domains[1];
						if($u[4] == 1)
							echo "<li data-theme=\"e\">";
						else
							echo "<li>";
						echo "<a rel=external href=\"afficher.php?uid="
							.$u[0]."\">".$u[1]." ".$u[2]
							." <font style=\"font-size: 90%;\">("
							.$domain.")</font></a>";
						if ($u[0] != $_SESSION["userid"]) 
							echo "<a href=\"javascript:conf(".$u[0].")\">Supprimer</a>";
						echo "</li>\n";
					} else {
						echo "<a rel=external href=\"afficher.php?uid="
							.$u[0]."\">".$u[1]." ".$u[2] . " </a>";
						if ($u[0] != $_SESSION["userid"]) 
							echo "<a href=\"javascript:conf(".$u[0].")\">Supprimer</a>";
						echo "</li>\n";
					}
				}
			?>
		</ul>
		</div>
		</div>
		</div>
	</body>
</html>