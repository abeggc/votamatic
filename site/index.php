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
$users = array();
global $sql_table_utilisateurs;
$sql = "SELECT u_id, nom, prenom, uniqueid FROM $sql_table_utilisateurs " .
		"ORDER BY nom ASC, prenom ASC;";
$result = $bd->query($sql);
while($row = $result->fetch_object()) {
 	$users[] = array($row->u_id, $row->nom, $row->prenom, $row->uniqueid);
 }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="author" content="Christian Abegg" />
		<meta name="description" content="Votamatic: Système de sondages optimisé pour appareils mobiles" />
		<meta name="keywords" content="sondage, ntic, unige, votamatic, auditoire" />
		
		<title>Votamatic</title>
		<link rel="stylesheet" href="jquery.mobile-1.0.1.min.css" />
		<link rel="stylesheet" href="jqm-docs.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>

	</head>
	<body>
	<div data-role="page" class="type-home">
	  <div data-role="content">
		<div class="content-secondary">
			<div id="jqm-homeheader">
				<h1 id="jqm-logo"><img src="images/votamatic.png" width=72 height=72 alt="Votamatic" />Votamatic</h1>
				<p>Système de sondages optimisé pour appareils mobiles</p>
			</div>
	
			<p class="intro"><strong>Bienvenue <?php 
			if(isset($_SESSION["prenom"])) {
					echo $_SESSION["prenom"];
			}?></strong></p>
					<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="f">
			<li data-role="list-divider">Menu</li>
				<?php
		if(isset($_SESSION["userid"]) && $_SESSION["userid"] > 0) {
	?>

			<?php
				echo "<li><a rel=external href=\"gestion.php?uid=".$_SESSION["userid"]."\">Gestion de mes sondages</a></li>\n";
				if (isset($_SESSION["role"]) && $_SESSION["role"] == 1) {
					echo "<li><a rel=external href=\"admin.php\">Administration des utilisateurs</a></li>\n";
				}
			?>
		
		<?php
		}
		?>

			<li><a rel=external href="aide.php">Aide</a></li>
			<li><a rel=external href="apropos.php">A propos de</a></li>
			<?php
			if(!$_SESSION["authShib"]) {
				print "<li><a rel=external href=\"auth/\">Connexion</a></li>\n";
			}
			?>	
		</ul>
		</div><!--/content-primary-->	
		
		<div class="content-primary">
		<h3>Liste des enseignants</h3>
		<ul data-role="listview" data-filter="true" data-filter-placeholder="Recherche d'un enseignant" data-inset="true" data-theme="c" data-dividertheme="b">
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
						echo "<li><a rel=\"external\" href=\"afficher.php?uid="
							.$u[0]."\">".$u[1]." ".$u[2]
							." <font style=\"font-size: 90%;\">("
							.$domain.")</font></a></li>\n";
					} else {
						echo "<li><a rel=\"external\" href=\"afficher.php?uid="
							.$u[0]."\">".$u[1]." ".$u[2] . " </a></li>\n";
					}
				}
			?>
		</ul>
		</div>
		</div>
		</div>
	</body>
</html>
