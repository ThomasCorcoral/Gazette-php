<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// affichage de l'entête
em_aff_entete('L\'actu', 'L\'actu');

lpl_aff_page();

// pied de page 
em_aff_pied();

// fin du script
ob_end_flush();

//_______________________________________________________________
/**
 * Permet de récupérer le nombre d'article présent dans la bdd
 *
 * @return 	int	 $t['COUNT(arID)'] Nombre d'articles
 */
function lpl_recup_nb_article() {

  // ouverture de la connexion à la base de données
  $bd = em_bd_connecter();

  // Récupération du nombre d'articles
  $sql = "SELECT COUNT(arID) FROM article";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
  $t = mysqli_fetch_assoc($res);

  return $t['COUNT(arID)'];

  // Libération de la mémoire associée au résultat de la requête
  mysqli_free_result($res);

  // Fermeture de la bdd
  mysqli_close($bd);

}

//_______________________________________________________________
/**
 * Fonction qui affiche les différentes pages
 * d'actualités en les triants par mois de
 * publication
 * 
 * @global $_POST
 */
function lpl_aff_page() {

  $nbmax = lpl_recup_nb_article() + 1;
  $nbarticle = 0;

  // On détermine le nombre de page qu'il nous faut, en ayant 4 articles par page
  $nbpage = round(($nbmax/4), 0, PHP_ROUND_HALF_UP);

  // On initialise deux compteurs
  $cpt = 1;
  $cpt2 = 0;

  echo '<main>',
        '<section>',
        '<h2>Navigation</h2>',
        '<form action="actus.php" method="post">',
          '<p>Pages : ';

  $save_button = 1;

  // On affiche la barre de navigation de page
  // en fonction du nombre de pages que l'on a besoin
  for($i = 1; $i <= $nbpage; $i++)
  {
    if(isset($_POST[$i]) == true)
    {
      $save_button = $i;
    }
  }

  while($cpt <= $nbpage)
  {
    if($cpt == $save_button)
    {
      echo '<span class="selectbut">',
            '<input type="submit" name=', $cpt,' value=', $cpt, '>',
          '</span>';
    }
    else
    {
      echo '<input type="submit" name=', $cpt, ' value=', $cpt, '>';
    }
    $cpt++;
  }

  echo '<p>',
       '</form>',
      '</section>';

  $res = 0;

  // On récupère les articles à afficher dans les différentes pages
  for($i = 1; $i <= $cpt; $i++){
    if(isset($_POST[$i]) == true){
      $res = 1;
    }
  }
  if($res == 0){
    lpl_recup_contenu($cpt2,4);
  }

  $cpt = $cpt - 1;

  for($i = 1; $i <= $cpt; $i++)
  {
    if(isset($_POST[$i]))
    {
      lpl_recup_contenu($cpt2,4);
    }
    $cpt2 = $cpt2 + 4;
  }
}

//_______________________________________________________________
/**
 *  Récupération des informations nécessaires pour ensuite,
 *  afficher les actualités par page, en fonction de min et max.
 *
 *  @param    int    $min  Valeur minimale de l'id de l'article
 *  @return   int    $max  Valeur maximale de l'id de l'article
 */
function lpl_recup_contenu($min, $max) {

  // ouverture de la connexion à la base de données
  $bd = em_bd_connecter();

  // Récupération de l'article et des informations sur le titre, le resumé, et sa date de publication selon min et max
  $sql = "SELECT arID, arTitre, arResume, arDatePublication FROM article ORDER BY arDatePublication DESC LIMIT $min,$max";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

  // pas d'articles --> fin de la fonction
  if (mysqli_num_rows($res) == 0) {
      eml_aff_erreur ('Identifiant d\'article non reconnu.');
      mysqli_free_result($res);
      mysqli_close($bd);
      return;         // ==> fin de la fonction
  }

  $tab = mysqli_fetch_assoc($res);
  $tab1 = eml_bd_select_articles($bd, $sql);

  $date = (int) $tab['arDatePublication'];

  // On convertit la valeur de date en mois et année
  $month = lpl_date_to_month($date);
  $year = lpl_date_to_year($date);

  // On affiche les actualités
  lpl_aff_actus($tab1, $month, $year);

  // Fermeture de la bdd
  mysqli_close($bd);
}

//_______________________________________________________________
/**
 *  Affichage des actualités contenues dans le tableau tab1,
 *  résultant de la requête sql.
 *
 *  @param    string    $tab1  Tableau résultant de la requête sql
 *  @param    int       $month  Valeur du mois
 *  @param    int       $year   Valeur de l'année
 */
function lpl_aff_actus($tab1, $month, $year) {

  echo '<section>';

  foreach ($tab1 as $value) {

    $date = (int) $value['arDatePublication'];
    $month2 = lpl_date_to_month($date);
    $year2 = lpl_date_to_year($date);

    if($month2 != $month){
      $month = $month2;
      echo '</section>',
      '<section>';
    }

    if($year2 != $year){
      $year = $year2;
    }
    lpl_aff_une_actu($value,$month,$year);
  }

    echo   '</section>',
      '</main>';
}

//_______________________________________________________________
/**
 * Affichage d'une actualité (dynamiquement) selon le tableau résultant de la bdd,
 * et selon le mois et l'année.
 *
 *  @param    string    $value  Tableau résultant de la requête sql
 *  @param    int       $month  Valeur du mois
 *  @param    int       $year   Valeur de l'année
 */
function lpl_aff_une_actu($value, $month, $year) {

  $value = em_html_proteger_sortie($value);
  $id = $value['arID'];
  $imgFile = '../upload/' . $id . '.jpg';

  echo  '<h2>', $month, ' ', $year, '</h2>',
        '<article class="resume">',
                '<img src="', $imgFile, '" alt="Photo d\'illustration |', $value['arTitre'], '">',
                '<h3>', $value['arTitre'], '</h3>',
                '<p>', $value['arResume'], '</p>',
                '<footer><a href="../php/article.php?id=', $id, '">Lire l\'article</a></footer>',
          '</article>';
}

//_______________________________________________________________
/**
 *  Affchage d'un message d'erreur dans une zone dédiée de la page.
 *
 *  @param  String  $msg    Le message d'erreur à afficher.
 */
function eml_aff_erreur($msg) {
    echo '<main>',
            '<section>',
                '<h2>Oups, il y a une erreur...</h2>',
                '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
                '<blockquote>', $msg, '</blockquote>',
            '</section>',
        '</main>';
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format mois
 *
 *  @param  int     $date   la date à convertir.
 *  @return string  $month  la chaîne qui représente le mois
 */
function lpl_date_to_month($date) {

    $mois = substr($date, -8, 2);

    $month = em_get_tableau_mois();

    return $month[$mois - 1];
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format AAAA
 *
 *  @param  int     $date   la date à afficher.
 *  @return string  $annee  la chaîne qui représente l'année
 */
function lpl_date_to_year($date) {

    $annee = substr($date, 0, -8);

    return $annee;
}

//_______________________________________________________________
/**
 *  Séléction articles et parcours des résultats
 *
 *  @param objet     $bd     Connecteur sur la bd ouverte
 *  @param string    $sql    Requête SQL
 *  @return string   $ret    le tableau des resultats
 */
function eml_bd_select_articles($bd, $sql) {

    // envoi de la requête au serveur de bases de données
    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    // tableau de résultat (à remplir)
    $ret = array();

    // parcours des résultats
    while ($t = mysqli_fetch_assoc($res)) {
        $ret[$t['arID']] = $t;
    }

    mysqli_free_result($res);

    return $ret;
}

?>
