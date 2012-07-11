/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

$(document).ready(function(){ 

$.get('sondage.php', {sid: sid, mode: "edition"}, showEditForm, "text");
}  );

function annuler() {
	var txt = "Quitter cette page impliquera la perte de vos modifications. Etes-vous sûr ?";
	if(confirm(txt)) {
		$(location).attr('href',"gestion.php");
	}
}

function showEditForm(res)
{
  obj = jQuery.parseJSON(res);
  $("#titre").html(obj.texte).trigger('create');
  $formulaire = "<form id=\"sondageEditForm\" action=\"javascript:\" >";
  $formulaire += "<div data-role=\"fieldcontain\"><label for='sujet'><strong>Titre du sondage</strong></label><input type='text' id='sujet' name='sujet'/></div>\n";
  $formulaire += "<div data-role=\"fieldcontain\"><label for='visibilite' class=\"select\">Résultats publics</label><select id='visibilite' name='visibilite' data-native-menu=\"false\"><option value=0>Oui</option><option value=1>Non</option></select></div>";
  $formulaire += "<div data-role=\"fieldcontain\"><label for='duree' class=\"select\">Durée du sondage</label><select id='duree' name='duree' data-native-menu=\"false\"><option value=30>30 secondes</option><option value=60>1 minute</option><option value=90>90 secondes</option><option value=120>2 minutes</option><option value=0>Gestion manuelle</option></select></div><hr>";
  $formulaire += "<div id=\"questions\" data-role=\"collapsible-set\">";
  $formulaire += "</div><a href=\"javascript:ajoutQuestion()\" data-role=\"button\" data-inline=\"true\" data-icon=\"plus\">Ajouter une question</a>"; 
  $formulaire += "<a href=\"javascript:annuler()\" data-role=\"button\" data-inline=\"true\">Annuler</a>";
  
  $formulaire += "<input type=\"submit\" data-inline=\"true\" value=\"Enregistrer le sondage\"/>\n";
  $formulaire += "</form>";
  $("#sondage").append($formulaire).trigger('create');
  for(i = 0; i<obj.questions.length; i++) {
  	ajoutQuestion(i);
  	for(j = 0; j<obj.questions[i].reponses.length; j++) {
  		ajoutReponse(i,j);
  	}
 }
 
  $("#sujet").val(obj.texte);
  $("#visibilite").val(obj.visibilite);
  $("#visibilite").selectmenu("refresh");
  $("#duree").val(obj.duree);
  $("#duree").selectmenu("refresh");
  for($i = 0; $i<obj.questions.length; $i++) {
  	$("#q"+$i).val(obj.questions[$i].texte);
  	$("#type-q"+$i).val(obj.questions[$i].type);
  	$("#type-q"+$i).selectmenu("refresh");
  	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  		$("#q"+$i+"-r"+$j).val(obj.questions[$i].reponses[$j].texte);
  	}
  }
  
    $('#sondage').submit(function() {
        var res = $.toJSON($("form").updateSondage());
        res = res.replace(/&/g, "__and__"); //problème avec php si & dans le json
        res = res.replace(/\+/g, "__plus__"); //json_decode converti les + en espaces

        if(!checkSondage()) {
          		alert("Pour être enregistré, le sondage doit avoir un titre et au moins une question avec deux réponses");
          		return false;
        }

        $.post("sondage.php", "action=edition&sid="+obj.s_id+"&sondage="+res, function(data) { afficheRes(data); });
        
        return false;
    });

}

// Ajoute une réponse à la question passée en paramètre
function ajoutReponse(qid,rid) {
	if(rid == null) {
		reponse=new Object();
		reponse.texte = "";
		reponse.r_id = 0;
		reponse.nb_voix = 0;
		obj.questions[qid].reponses.push(reponse);
		$j = obj.questions[qid].reponses.length-1;
	} else {
		$j = rid;
	}
	newr = "<div data-role=\"fieldcontain\" id=\"input-q"+qid+"-r"+$j+"\">";
  	newr += "<label for='q"+qid+"-r"+$j+"'>&nbsp;Réponse "+($j+1)+"&nbsp;</label>";
  	newr += "<input type='text' id='q"+qid+"-r"+$j+"' name='q"+qid+"-r"+$j+"'><a href=\"javascript:supprReponse("+qid+","+$j+")\" data-inline=\"true\" data-role=\"button\" data-icon=\"minus\" data-iconpos=\"notext\"></a></div>";
  	$("#input-rq"+qid).append(newr).trigger('create');
}

// Supprime la réponse à la question passée en paramètre
function supprReponse(qid,rid) {
	obj.questions[qid].reponses[rid].texte = "";
  	$("#input-q"+qid+"-r"+rid).remove();
}

// Supprime la question passée en paramètre
function supprQuestion(qid) {
	obj.questions[qid].texte = "";
	for(i = 0; i<obj.questions[qid].reponses.length; i++) {
		obj.questions[qid].reponses[i].texte = "";
	}
	$("#input-q"+qid).remove();
}

// Ajout une nouvelle question au formulaire
function ajoutQuestion(q) {
	if(q == null) {
		question=new Object();
		question.texte = "";
		question.type = 1;
		question.q_id = 0;
		reps = [];
		question.reponses = reps;
		obj.questions.push(question);
		qid = (obj.questions.length-1);
	} else {
		qid = q;
	}

	s = "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"d\" id=\"input-q"+qid+"\">";
    s += "<h3>Question "+(qid+1)+"</h3>";
	s += "<div data-role=\"fieldcontain\"><label for='q"+qid+"'><strong>Libellé</strong></label><input type='text' id='q"+qid+"' name='q"+qid+"'/></div>";
    s += "<div data-role=\"fieldcontain\"><label for='type-q"+qid+"' class=\"select\">Type de question</label><select id='type-q"+qid+"' name='type-q"+qid+"' data-native-menu=\"false\"><option value=1>Réponses multiples</option><option value=2>Réponse unique</option><option value=3>K'</option></select></div>";
    s += "<div id=\"input-rq"+qid+"\">";
    s += "</div><a href=\"javascript:ajoutReponse("+qid+")\" data-role=\"button\" data-inline=\"true\" data-icon=\"plus\">Ajouter une réponse</a>";
    s += "<a href=\"javascript:supprQuestion("+qid+")\" data-role=\"button\" data-inline=\"true\" data-icon=\"minus\">Supprimer la question</a>";
    s += '</div>\n';
  	$("#questions").append(s).trigger('create');
  
	// Création d'une question -> ajout automatique de 4 réponses
  	if(q == null) {
  		for(i = 0; i < 4; i++) {
  			ajoutReponse(qid);
  		}
  	}
}

function afficheRes(data) {
	$("#sondage").html("");
	$("#vote").html("");
	$("#fullresponse").html("");
	$("#sf").html("");
	//$("#result").html("reponse php: "+data);
	$(location).attr('href',"gestion.php");
}

$.fn.updateSondage = function()
{
// Création objet JSON sondage
    var o = {};
    var a = this.serializeArray();
    // Récupérer le type de question
    for($i = 0; $i<obj.questions.length; $i++) {
    	obj.questions[$i].type = $("#type-q"+$i).val();
    	//alert("q:"+$i+", type:"+obj.questions[$i].type);
    }
    $.each(a, function() {
    	if(this.name.indexOf("-")>-1 && this.name.indexOf("type")<0) {
    		var t = this.name.split("-");
    	    question = getQuestionID(this.name);
    	    reponse = getReponseID(this.name);
    		//alert(this.name+" "+this.value+" (question:"+getQuestionID(this.name)+", reponse:"+getReponseID(this.name)+")");
    		obj.questions[question].reponses[reponse].texte = this.value;
    		//obj.questions[question].reponses[reponse].texte = this.value;
    	} else if (this.name.indexOf("q")==0 && this.name.indexOf("r")<0) {
    		//var t = this.name.split("-");
    		question = getQuestionID(this.name);
    		obj.questions[question].texte = this.value;
    	} else if (this.name == "sujet"){
    		obj.texte = this.value;
    		//alert(this.name+" "+this.value+" (question:"+getQuestionID(this.name)+")");
    	} else if (this.name == "visibilite") {
    		obj.visibilite = this.value;
    	} else if (this.name == "duree") {
    		obj.duree = this.value;
    	}
    	
    	//obj.questions[this.name].reponses[this.value].nb_voix = 1;
    });
    return obj;
};

function getQuestionID(str) {
	tab = str.split("-");
	return tab[0].substring(1);
}

function getReponseID(str) {
	tab = str.split("-");
	return tab[1].substring(1);
}

// Vérification si le sondage peut être enregistré
// Il faut un titre, une question avec au moins 2 réponses (1 pour type k')
function checkSondage() {
	if(obj.texte.trim().length == 0)
		return false;
		
	var ok = false;
	var nbrepok = 0;
	ok = false;
  	
  	for(i = 0; i<obj.questions.length; i++) {
  		if(obj.questions[i].texte.trim().length == 0) {
  			continue;
  		}
  		for(j = 0; j<obj.questions[i].reponses.length; j++) {
  			if(obj.questions[i].reponses[j].texte.trim().length > 0) {
  				nbrepok++;
  			}
  		}
  		if(nbrepok > 1 || (nbrepok >= 1 && obj.questions[i].type == 3))
  			ok = true;
  		else
  			return false;
  		nbrepok = 0;
  	}
	if(!ok)
		return false;

  return true;
}
