<?php
// Bibliothèque d'analyse de fichier HTML utile pour créer l'aspirateur
include('./simple HTML DOM/simple_html_dom.php');

// Met la limite de durée d'excution du script au maximum supporté par Wampserver
// afin que le script s'exécute le plus longtemps possible
set_time_limit(9999);

//Dossier des captures de critiques faites par l'aspirateur
$dossier_aspirateur='rep_aspirateur';	

// Si le dossier "rep_aspirateur" n'existe pas on le crée
if (!is_dir('./'.$dossier_aspirateur))
	mkdir('./'.$dossier_aspirateur,0777);

$domaine='https://www.allocine.fr';
$series=$domaine.'/series-tv/';

function fichier_html_distant_existe($url){
  // Ouvrir le fichier
  $check = @fopen($url, 'r');
  
  // Vérifier si le fichier existe
  if(!$check){
    return False;
  }else{
    return True;
  }
}

//Nombre de pages de séries à explorer
//$nbpages_series=1121; // en date du 09/01/2022

// Recherche automatique du nombre maximal de séries affichées à la racine de Allocine
$htmlcritique=file_get_html($series);
$retcritique = $htmlcritique->find("span");
$nbpages_series=1;
foreach($retcritique as $elementcritique) {
	if(isSet($elementcritique->class) && substr($elementcritique->class,-21)=='button button-md item'){
		if ((int)($elementcritique->plaintext)>$nbpages_series)
			$nbpages_series=(int)($elementcritique->plaintext);
	}	
}
print("<h2>Nombre de pages de séries à parcourir détecté: $nbpages_series</h2>");

/////////////////////////////

$html = new simple_html_dom();
$nomfichiermemorisation='./'.$dossier_aspirateur.'/memorisation.txt';


// Si le fichier qui mémorise l'en-cours pour reprise de téléchargement existe
// alors on le lit pour recommencer à télécharger là où on s'est arrêté!
if (file_exists($nomfichiermemorisation)){
	$lignes=file($nomfichiermemorisation);
	$pagedepart=(int)($lignes[0]);
	$codeseriedepart=(int)$lignes[1];
	$numordre=(int)$lignes[2];
	print("<h1>Reprise d'aspiration</h1>");
	print("Dernière page de série en cours d'aspiration mémorisée: ".$pagedepart."<br/>");
	print("Dernier code série aspiré mémorisé: ".$codeseriedepart."<br/>");
	print("Dernier n° d'ordre de série aspirée mémorisé: ".$numordre."<br/><br/>");
}
else {
	$pagedepart=1;
	$codeseriedepart=-1;
	$numordre=0;
	print("<h1>Nouvelle aspiration de l'ensemble des séries</h1>");
}

// Début réel du téléchargement d'aspiration
for ($i=$pagedepart;$i<=$nbpages_series;$i++){
	
	// Si le fichier des critiques existe bien (Allocine a des bugs avec des pages inexistantes)
	if (fichier_html_distant_existe($series.'?page='.$i)){
		// On se place sur la page n°i qu'on télécharge
		$html=file_get_html($series.'?page='.$i);
		
		if ($i<10)
			$zerosnumpage='000';
		elseif ($i<100)
			$zerosnumpage='00';
		elseif ($i<1000)
			$zerosnumpage='0';
		else
			$zerosnumpage='';
		
		print("<h3>Page des listes de séries: ".$i."</h3>");
		$ret = $html->find("a");
		
		$autorisesuite=false;
		
		foreach($ret as $element) {
			//Si on a trouvé une entrée de série
			if(isSet($element->class) && $element->class=='meta-title-link'){
				$t=explode('=',$element->href);
				$t=explode('.',$t[1]);

				if (($autorisesuite==false)&&($codeseriedepart!=-1)&&($t[0]==$codeseriedepart)){
					$autorisesuite=true;
				}
				elseif (($autorisesuite==false)&&($codeseriedepart==-1)){
					$autorisesuite=true;
				}
				
				
				//Si on est autorisé à reprendre l'aspiration (ou à la commencer)
				if ($autorisesuite && $t[0]!=$codeseriedepart){
					$codeseriedepart=-1;
					//Construction de l'URL du site contenant la 1ère page des critiques de la série
					$url_critique_spectateur=$domaine."/series/ficheserie-".$t[0]."/critiques/";
					print("<b>Code série: ".$t[0]."</b> - URL critiques: ".$url_critique_spectateur);
					
					$numordre++;
					
					if ($numordre<10)
						$zerosnumordre='0000';
					elseif ($numordre<100)
						$zerosnumordre='000';
					elseif ($numordre<1000)
						$zerosnumordre='00';
					elseif ($numordre<10000)
						$zerosnumordre='0';
					else
						$zerosnumordre='';

					// Si le fichier des critiques existe bien (Allocine a des bugs avec des pages inexistantes)
					if (fichier_html_distant_existe($url_critique_spectateur)){
						//Récupération sur la 1ère page du nombre de pages total de critiques de la série pour parcours de toutes les critiques
						//Scan des <span> de classe="button button-md item" (contient le nombre total de pages si élevé)
						$htmlcritique=file_get_html($url_critique_spectateur);
						$titre = $htmlcritique->find("title");
						
						//Si le fichier n'est pas redirigé vers la fiche descriptive (=page sans critique)
						//alors c'est bien un fichier de critiques
						if (substr($titre[0]->innertext,0,12)=="Critiques de"){
						
							$retcritique = $htmlcritique->find("span");
							$nombre_pages_critiques=1;
							foreach($retcritique as $elementcritique) {
								if(isSet($elementcritique->class) && substr($elementcritique->class,-21)=='button button-md item'){
									if ((int)($elementcritique->plaintext)>$nombre_pages_critiques)
										$nombre_pages_critiques=(int)($elementcritique->plaintext);
								}	
							}
							
							//Scan des <a> de classe="button button-md item" (contient le nombre total de pages si peu élevé)
							$retcritique = $htmlcritique->find("a");
							foreach($retcritique as $elementcritique) {
								if(isSet($elementcritique->class) && substr($elementcritique->class,-21)=='button button-md item'){
									if ((int)($elementcritique->plaintext)>$nombre_pages_critiques)
										$nombre_pages_critiques=(int)($elementcritique->plaintext);
								}	
							}
							
							print("&nbsp;&nbsp;--> Nb pages: ".$nombre_pages_critiques."<br/>");

							//Parcours des pages de critiques et chargement
							for ($j=1;$j<=$nombre_pages_critiques;$j++){	
								if ($j<10)
									$zeroscomment='00';
								elseif ($j<100)
									$zeroscomment='0';
								else
									$zeroscomment='';
								
								print("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$url_critique_spectateur."?page=".$j."<br/>");
								
								$fichier_critique_page=file_get_contents($url_critique_spectateur."?page=".$j);

								
								$chemin_critique_page='./'.$dossier_aspirateur.'/critiquesspect'.$zerosnumpage.$i.'_'.$zerosnumordre.$numordre.'-'.$t[0].'_'.$zeroscomment.$j.'.html';							
								$fichier_critique_disque=fopen($chemin_critique_page,'w');
								//Ou bien:
								//file_put_contents($chemin_critique_page,$fichier_critique_page);
								fputs($fichier_critique_disque,$fichier_critique_page);
								fclose($fichier_critique_disque);

							}
						}
						else
							print("&nbsp;&nbsp;--> Nb pages: 0<br/>");
						
						//Mémorisation de la position faite pour reprise
						$fichier_repriseaspiration=fopen($nomfichiermemorisation,'w');
						//Mémorisation de la page des séries en cours d'exploration
						fwrite($fichier_repriseaspiration,$i."\n");
						//Mémorisation du code série qui vient d'être explorée
						fwrite($fichier_repriseaspiration,$t[0]."\n");
						//Mémorisation du numéro d'ordre de la série dans la liste des sauvegardes
						fwrite($fichier_repriseaspiration,$numordre);
						fclose($fichier_repriseaspiration);
					}
				}
				

				
			}	
		}
		print("<br/><br/>");
	}
}
?>