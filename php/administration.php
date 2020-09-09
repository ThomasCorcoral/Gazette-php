<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// si l'utilisateur n'est pas authentifié
tcl_check_open();

// génération de la page
em_aff_entete('Administration', 'Administration');

// si formulaire soumis, traitement enregistrement infos des utilisateurs
if (isset($_POST['btnEnregistrer'])) {
  lpl_traitement_enregistrer_infos_utilisateurs();
}

lpl_aff_utilisateurs_et_leurs_droits();

em_aff_pied();

ob_end_flush(); //FIN DU SCRIPT

/**
 * Fonction qui vérifie que l'ouverture de la page peut être effectuée.
 * Pour ce faire, elle vérifie que l'utilisateur est connecté et qu'il 
 * possède le droit d'administration.
 * Sinon elle redirige vers la page index.php
 */
function tcl_check_open()
{
    if(isset($_SESSION['user']))
    {
        if(!$_SESSION['user']['administrateur'])
        {
            header('Location: ../index.php');
            exit;
        }
    }
    else
    {
        header('Location: ../index.php');
        exit;
    }
}

//___________________________________________________________________
/**
 * Affichage des utilisateurs inscrits et de leurs droits
 * et de leurs statistiques.
 *
 */
function lpl_aff_utilisateurs_et_leurs_droits() {

  // ouverture de la connexion à la base de données
  $bd = em_bd_connecter();

  $sql = "SELECT utPseudo, utStatut FROM utilisateur";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
  $tab = mysqli_fetch_assoc($res);
  $tab1 = lpl_bd_select_utilisateur($bd, $sql);

  echo
      '<main>',
      '<section>',
          '<h2>Modifications des droits des utilisateurs inscrits</h2>',
          '<form action="administration.php" method="post">',
          '<table>';
  $cpt = 1;

  foreach ($tab1 as $value) {

    echo '<div>';

    $nomstatut = "statut" . $cpt;

    $pseudo = em_html_proteger_sortie($value['utPseudo']);
    $statut = $value['utStatut'];

    lpl_aff_form($pseudo, $statut, $nomstatut);

    $cpt++;

    echo '</div>';
  }
  echo
          '<tr>',
              '<td colspan="2">',
                  '<input type="submit" name="btnEnregistrer" value="Enregistrer">',
              '</td>',
          '</tr>',
        '</table>',
        '</form>',
      '</section></main>';


  mysqli_free_result($res);
  // fermeture de la connexion à la base de données
  mysqli_close($bd);
}

//___________________________________________________________________
/**
 * Affichage du formulaire contenant les informations
 * et les différentes statistiques des utilisateurs.
 *
 *  @param  String   $pseudo      Pseudo de l'utilisateur
 *  @param  int      $statut      Statut de l'utilisateur
 *  @param  String   $nomstatut   Libelle du statut de l'utilisateur
 */
function lpl_aff_form($pseudo, $statut, $nomstatut) {

    if($statut == 0){
      $nomstatut0 = "Utilisateur lambda";
      $nomstatut1 = "Rédacteur";
      $nomstatut2 =  "Administrateur";
      $nomstatut3 = "Administrateur et rédacteur";

      $numstatut1 = 1;
      $numstatut2 = 2;
      $numstatut3 = 3;
    }
    else if($statut == 1){
      $nomstatut0 = "Rédacteur";
      $nomstatut1 = "Utilisateur lambda";
      $nomstatut2 = "Administrateur";
      $nomstatut3 = "Administrateur et rédacteur";

      $numstatut1 = 0;
      $numstatut2 = 2;
      $numstatut3 = 3;
    }
    else if($statut == 2){
      $nomstatut0 = "Administrateur";
      $nomstatut1 = "Utilisateur lambda";
      $nomstatut2 = "Rédacteur";
      $nomstatut3 = "Administrateur et rédacteur";

      $numstatut1 = 0;
      $numstatut2 = 1;
      $numstatut3 = 3;
    }
    else if($statut == 3){
      $nomstatut0 = "Administrateur et rédacteur";
      $nomstatut1 = "Utilisateur lambda";
      $nomstatut2 = "Rédacteur";
      $nomstatut3 = "Administrateur";

      $numstatut1 = 0;
      $numstatut2 = 1;
      $numstatut3 = 2;
    }

    $nbComm = lpl_aff_nombre_commentaire($pseudo);
    $nbArticle = lpl_aff_nombre_article($pseudo);
    $nbMoyenComm = lpl_aff_nombre_moyen_commentaire($pseudo);

    echo '<tr class = "pseudo"><td><label for=\"pseudo\"></label></td>',
         '<td><h4>', $pseudo ,'</h4></td></tr>';

    lp_aff_liste_statut_utilisateur('Droits de l\'utilisateur :', $nomstatut, $statut, $nomstatut0, $nomstatut1, $nomstatut2, $nomstatut3, $numstatut1, $numstatut2, $numstatut3);

    echo '<tr><td>Nombre de commentaires publiés : </td><td>', $nbComm, '</td></tr>',
         '<tr><td>Nombre d\'articles publiés : </td><td>', $nbArticle, '</td></tr>',
         '<tr><td>Nombre moyen de commentaires par article publié : </td><td>', $nbMoyenComm, '</td></tr>';
}

//___________________________________________________________________
/**
 * Récupère et retourne le nombre de commentaires total.
 *
 *  @param  String   $pseudo   Pseudo de l'utilisateur
 *  @return int      $nbComm   Nombre de commentaires
 */
function lpl_aff_nombre_commentaire($pseudo) {

  // ouverture de la connexion à la base
  $bd = em_bd_connecter();

  $sql = "SELECT coID FROM commentaire WHERE coAuteur = '{$pseudo}'";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

  $nbComm = mysqli_num_rows($res);

  mysqli_close($bd);

  return $nbComm;

}

//___________________________________________________________________
/**
 * Récupère et retourne le nombre d'article publié pour un utilisateur.
 *
 *  @param  String   $pseudo      Pseudo de l'utilisateur
 *  @return int      $nbArticle   Nombre d'articles
 */
function lpl_aff_nombre_article($pseudo) {

  // ouverture de la connexion à la base
  $bd = em_bd_connecter();

  $sql = "SELECT arID FROM article WHERE arAuteur = '{$pseudo}'";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

  $nbArticle = mysqli_num_rows($res);

  mysqli_close($bd);

  return $nbArticle;

}

//___________________________________________________________________
/**
 * Calcul et retourne le nombre de commentaires moyen,
 * en fonction du nombre d'article publié par l'utilisateur,
 * et du nombre de commentaires total sur ces articles.
 *
 *  @param  String   $pseudo        Pseudo de l'utilisateur
 *  @return int      $nbMoyenComm   Nombre moyen de commentaires
 */
function lpl_aff_nombre_moyen_commentaire($pseudo) {

  // ouverture de la connexion à la base
  $bd = em_bd_connecter();

  $sql = "SELECT coID FROM commentaire INNER JOIN article ON coArticle = arID WHERE arAuteur = '{$pseudo}'";

  $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

  $nbMoyenComm = mysqli_num_rows($res);

  if($nbMoyenComm != 0){
    $nbMoyenComm = $nbMoyenComm / lpl_aff_nombre_article($pseudo);
  }

  mysqli_close($bd);

  return $nbMoyenComm;

}

//___________________________________________________________________
/**
 * Enregistre les nouvelles informations pour un utilisateur
 * dans la bdd.
 *
 *  @global  $_POST
 */
function lpl_traitement_enregistrer_infos_utilisateurs() {

  // ouverture de la connexion à la base de données
  $bd1 = em_bd_connecter();

  $sql1 = "SELECT utPseudo, utStatut FROM utilisateur";

  $res1 = mysqli_query($bd1, $sql1) or em_bd_erreur($bd1, $sql1);
  $tab1 = mysqli_fetch_assoc($res1);
  $tab1bis = lpl_bd_select_utilisateur($bd1, $sql1);

  $cpt = 1;

  foreach ($tab1bis as $value) {

    $existe = 1;

    $pseudo = em_html_proteger_sortie($value['utPseudo']);
    $statut = "statut" . $cpt;
    $statut = $_POST[$statut];
    $oldStatut = $value['utStatut'];


    $sql = "SELECT rePseudo FROM redacteur WHERE rePseudo = '{$pseudo}'";
    $res = mysqli_query($bd1, $sql) or em_bd_erreur($bd1, $sql);
    $tab = mysqli_fetch_assoc($res);

    if(mysqli_num_rows($res) == FALSE){
      $existe = 0;
    }

    $sql2 = "UPDATE IGNORE utilisateur SET utStatut = '{$statut}' WHERE utPseudo = '{$pseudo}'";

    if(($statut == 1 || $statut == 2 || $statut == 3) && ($oldStatut == 0) && ($existe == 0)){
      $sql3 = "INSERT INTO redacteur(rePseudo, reBio, reCategorie, reFonction) VALUES ('{$pseudo}', '', 3, '')";
      mysqli_query($bd1, $sql3) or em_bd_erreur($bd1, $sql3);
    }

    if(($statut == 0) && ($oldStatut == 1 || $oldStatut == 2 || $oldStatut == 3) && ($existe == 1)){
      $sql4 = "DELETE FROM redacteur WHERE rePseudo = '{$pseudo}'";
      mysqli_query($bd1, $sql4) or em_bd_erreur($bd1, $sql4);
    }

    mysqli_query($bd1, $sql2) or em_bd_erreur($bd1, $sql2);

    $cpt++;
  }

  // fermeture de la connexion à la base de données
  mysqli_close($bd1);

}

//_______________________________________________________________
/**
 *  Séléction pseudo utilisateur et parcours des résultats
 *
 *  @param objet     $bd     Connecteur sur la bd ouverte
 *  @param string    $sql    Requête SQL
 *  @return string   $ret    le tableau des resultats
 */
function lpl_bd_select_utilisateur($bd, $sql) {

    // envoi de la requête au serveur de bases de données
    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    // tableau de résultat (à remplir)
    $ret = array();

    // parcours des résultats
    while ($t = mysqli_fetch_assoc($res)) {
        $ret[$t['utPseudo']] = $t;
    }

    mysqli_free_result($res);

    return $ret;
}

?>
