<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

require("libs/mysql.php");
require("session.php");
$bd = connexionBD();
checkSession();
date_default_timezone_set('UTC');
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="author" content="Christian Abegg" />
		<meta name="description" content="sondage" />
		<meta name="keywords" content="sondage, ntic, unige" />
		<title>Aide de Votamatic</title>

		<link rel="stylesheet" media="screen" href="jquery.mobile-1.0.1.min.css" />
		<link rel="stylesheet" media="print"  href="jqm-docs.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>
	</head>
	<body>

	<div data-url="aide.php" data-role="page">
        <div data-role="header">
            <h1 id="titre">Aide Votamatic</h1>
            <a href="index.php" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-left jqm-home">Accueil</a>
        </div>

        <div role="main" data-role="content">
        <div data-role="collapsible-set" data-theme="b" data-content-theme="d">
            <div data-role="collapsible" data-collapsed="false">
                <h2>Qu'est-ce que Votamatic</h2>
                <p>Votamatic est une application web permettant de participer à 
                des sondages et dont l'interface utilisateur est adaptée à des 
                petits appareils mobiles comme des smartphones ou tablettes.
                 Son usage est prévu pendant les cours afin d'améliorer l'interactivité,
                  vérifier la préparation ou la compréhension des étudiants.</p>
            </div>
            <div data-role="collapsible">
                <h2>Participer à un sondage</h2>
                
                        <h3>1. Choix de l'enseignant</h3>
                        <p>Sur la <a href="index.php" rel="external">page d'accueil</a>,
                         la liste des enseignants utilisant Votamatic est disponible.
                          Au dessus de la liste, un champ de recherche permet l'accès
                           immédiat à un enseignant particulier.</p>
                        <p>Pour choisir un enseignant, il suffit de cliquer sur 
                        son nom dans la liste</p>
                        <h3>2. Afficher le sondage</h3>
                        <p>Après le choix de l'enseignant, trois pages peuvent 
                        s'afficher</p>
                        <ul>
                            <li>le sondage en cours</li>
                            <li>les résultats du dernier sondage évalué</li>
                            <li>un message indiquant qu'il n'y a pas de sondage 
                            en cours</li>
                        </ul>
                        <p>Dans le premier cas, répondez aux questions et validez
                         votre choix. Les résultats du sondage s'afficheront dès
                          que l'enseignant l'aura fermé.</p>
                        <p>Dans les deux autres cas, la page est automatiquement
                         rafraîchie afin d'afficher dès que possible un nouveau 
                         sondage.</p>
            </div>
            <?php if(isset($_SESSION["userid"]) && $_SESSION["userid"] > 0) { ?>
            <div data-role="collapsible">
                <h2>Créer un sondage</h2>
                <p>Les membres du corps enseignant et du PAT peuvent créer des 
                sondages en choisissant «Gestion de mes sondages» du menu vert 
                de la page d'accueil. Dans la nouvelle page qui s'affiche, pressez
                 le bouton «Créer un sondage». Un formulaire contenant les éléments
                  suivant s'affichera:</p>
                <ol>
                    <li><strong>Titre du sondage:</strong> donnez un titre au sondage</li>
                    <li><strong>Résultats publics:</strong> indiquez si les résultats
                     du sondage seront visibles sur les appareils des participants 
                     (option recommandée) ou non</li>
                    <li><strong>Durée du sondage:</strong> choisissez le temps 
                    laissé aux participants pour répondre au sondage ou choisissez
                     l'option «Gestion manuelle» si vous voulez fermer le sondage
                      quand vous le désirez (option recommandée).</li>
                    <li><strong>Question 1:</strong> un sondage est composé au 
                    minimum d'une question avec un choix.
                        <ul>
                            <li><strong>Libellé:</strong> indiquez l'énoncé de 
                            la question</li>
                            <li><strong>Type de question:</strong> trois types 
                            de questions sont disponibles:
                                <ul>
                                    <li>Réponses multiples: une ou plusieurs 
                                    cases à cocher</li>
                                    <li>Réponse unique: une seule réponse possible</li>
                                    <li>K': répondre obligatoirement par «vrai» 
                                    ou «faux» à chacune des réponses</li>
                                </ul>
                            </li>
                            <li><strong>Réponse 1:</strong> indiquez un premier 
                            choix de réponse. Les champs Réponse 2, 3 et 4 non 
                            remplis seront ignorés. Si vous voulez ajouter plus 
                            de 4 réponses, pressez le bouton «Ajouter une réponse».
                             Un item de réponse peut être supprimé en pressant
                             le bouton «moins».</li>
                        </ul>
                    </li>
                    <li>Question 2, 3: si ces questions ne sont pas alimentées, 
                    elles seront ignorées. Si vous voulez ajouter plus de 3 questions
                     à votre sondage, cliquez sur le bouton «Ajouter une question».</li>
                    <li><strong>Enregistrez le sondage</strong> en pressant le 
                    bouton correspondant.</li>
                </ol>
            </div>
            <div data-role="collapsible">
                <h2>Ouvrir un sondage</h2>
                    <p>Une fois un sondage créé, celui-ci se trouve dans la catégorie
                     «Sondages prêts à l'évaluation» de la page de gestion de vos
                     sondages. Cliquez sur le titre du sondage et pressez le bouton
                     «Evaluer». Les participants trouveront le sondage en cliquant
                     sur votre nom dans la liste des enseignants de la page 
                     d'accueil. Si vous souhaitez communiquer l'adresse directe
                     de votre sondage ou la mettre dans une présentation PowerPoint,
                     ouvrez la page de gestion de vos sondages, cliquez sur le
                     titre de votre sondage et pressez le bouton «Lien» pour
                     afficher/copier l'adresse du sondage.</p>
                    <p>Une fois que les participants ont voté, vous pouvez fermer
                     le sondage en pressant le bouton «Fermer le sondage« qui se
                     trouve au sommet de la page affichant le sondage. Cette action
                      affichera le résultat du sondage sur votre appareil et, si 
                      vous aviez choisi de rendre public les résultats, sur ceux 
                      des participants. Un sondage évalué se trouve dans la 
                      catégorie «Sondages déjà évalués» de la page de gestion 
                      de vos sondages.</p>
            </div>
            <div data-role="collapsible">
                <h2>Modifier un sondage</h2>
                    <p>Une fois un sondage fermé, celui-ci ne peux pas être modifié.
                     Si vous désirez quand même le modifier, vous devrez effacer 
                     les résultats du sondage en pressant le bouton «RAZ» (Remise à Zéro).
                      Avant de le faire, vous pouvez exporter les résultats sous 
                      la forme d'un fichier de données .csv en pressant le bouton
                      «Export CSV». Vous pourrez ensuite ouvrir ce fichier dans 
                      un tableur comme Excel.</p>
                    <p>Une fois le sondage remis à zéro, celui-ci bascule à nouveau
                     dans la catégorie «Sondages prêts à l'évaluation« et le 
                     bouton «Edition» permet de le modifier à volonté. 
                     Finalement, le bouton «Supprimer» efface le sondage et les 
                     données qui lui sont éventuellement liées.</p>
            </div>
            <div data-role="collapsible">
                <h2>Conseils</h2>
                    <p>Votamatic est prévu pour un usage en temps réel pendant 
                    les cours. En conséquence, nous vous conseillons de ne pas 
                    mettre plus de 3 questions par sondage. Le temps nécessaire 
                    pour répondre au sondage et discuter des résultats devrait 
                    prendre moins de 10 minutes.</p>
                    <p>L'article de Caldwell présente les différents usages 
                    possibles d'un outil comme votamatic, celui de Robertson 
                    vous donnera des conseils pratiques généraux alors que 
                    l'article de Beatty démontre comment des questions bien 
                    faites soumises aux étudiants pendant un cours peuvent être 
                    un véritable moyen d'apprentissage.</p>
                    <ul>
                        <li>Caldwell JE. <a href="http://www.lifescied.org/cgi/content/abstract/6/1/9" target="_blank">Clickers in the Large Classroom: Current Research and Best-Practice Tips</a>. CBE-Life Sciences Education. 2007;6(1):9 -20</li>
                        <li>Robertson LJ. <a href="http://informahealthcare.com/doi/abs/10.1080/01421590050006179" target="_blank">Twelve tips for using a computerised interactive audience response system</a>. Medical Teacher. 2000;22(3):237-239.</li>
                        <li>Beatty ID, Gerace WJ, Dufresne RJ. <a href="http://arxiv.org/abs/physics/0508114" target="_blank">Designing Effective Questions for Classroom Response System Teaching</a>. arXiv:physics/0508114. 2005.</li>
                    </ul>
            </div>
            <?php } ?>
            <div data-role="collapsible">
                <h2>Protection des données</h2>
                <p>Afin de garantir un vote unique par personne, Votamatic 
                enregistre les identifiants des votants pour la durée de l'évalution
                du sondage. Dès que le sondage est clos, toutes les informations
                sur les votants sont automatiquement supprimées. Il n'est donc pas
                possible, <em>pour quiconque</em>, de savoir
                <ul><li>qui a participé à un sondage</li>
                    <li>qui a choisi quelles réponses, même pendant l'évaluation</li></ul>
                Grâce à cela, l'anonymat est garanti pour tous les utilisateurs de Votamatic.
                </p>
            </div>            
            <div data-role="collapsible">
                <h2>Contact</h2>
                <p>Pour toute question, remarque ou proposition d'amélioration :
                 <a href="mailto:votamatic@unige.ch">votamatic@unige.ch</a></p>
            </div>
        </div>
        </div>
	</div>
	</body>
</html>
