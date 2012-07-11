<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

/*
 * Gestion de la connexion à la base de données
 * La connexion à la base est retournée sous forme d'objet MySQLi
 */
 
$sql_prefix             = 'votamatic_';
$sql_table_sondages     = $sql_prefix.'sondages';
$sql_table_questions    = $sql_prefix.'questions';
$sql_table_reponses     = $sql_prefix.'reponses';
$sql_table_utilisateurs = $sql_prefix.'utilisateurs';

/* Connexion à MySQL */
function connexionBD() {

	/* Informations de connexion */
	$sql_host = 'localhost';
	$sql_user = 'username';
	$sql_pass = 'password';
	$sql_base = 'votamaticdb';

	/* Instanciation de l'objet représentant la bdd */
	$bd = new mysqli($sql_host, $sql_user, $sql_pass, $sql_base);

	return $bd;
}

/* Déconnexion de MySQL */
function deconnexionBD($bd) {
	$bd->close();
}

?>
