<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// si l'utilisateur n'est pas authentifié
if (!isset($_SESSION['user'])){
    header ('location: ../index.php');
    exit();
}

// si formulaire soumis, traitement de la demande de modification
if (isset($_POST['btnEnregistrer'])) {
    $erreurs = lpl_traitement_enregistrer();
}
else{
    $erreurs = FALSE;
}

// si formulaire soumis, traitement de la demande de modification de mdp
if (isset($_POST['btnEnregistrer2'])) {
    $erreurs2 = lpl_traitement_modif_mdp();
}
else{
    $erreurs2 = FALSE;
}

// si formulaire soumis, traitement enregistrement infos redacteur
if (isset($_POST['btnEnregistrer3'])) {
  lpl_traitement_enregistrer_infos_redacteur();
}

// si formulaire soumis, traitement enregistrement photo redacteur
if (isset($_POST['btnEnregistrer4'])) {
  lpl_traitement_enregistrer_photo_redacteur();
}

// génération de la page
em_aff_entete('Mon compte', 'Mon compte');

lpl_aff_infos_perso($erreurs);
lpl_aff_authentification_mdp($erreurs2);

// Si la personne connectée est un rédacteur ou administrateur il peut voir ses informations
if($_SESSION['user']['redacteur']|| $_SESSION['user']['administrateur']){
  lpl_aff_infos_redacteur();
  lpl_aff_photo_redacteur();
}

em_aff_pied();

ob_end_flush(); //FIN DU SCRIPT

//___________________________________________________________________
/**
 * Affichage des informations perso
 *
 *  @global array    $_POST
 *  @global array    $_SESSION
 *  @param objet  $erreurs  Tableau regroupant les erreurs relevées
 */
function lpl_aff_infos_perso($erreurs) {

    $anneeCourante = (int) date('Y');

    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnEnregistrer'])){
        $nom = em_html_proteger_sortie(trim($_POST['nom']));
        $prenom = em_html_proteger_sortie(trim($_POST['prenom']));
        $email = em_html_proteger_sortie(trim($_POST['email']));
        $jour = (int)$_POST['naissance_j'];
        $mois = (int)$_POST['naissance_m'];
        $annee = (int)$_POST['naissance_a'];
        $civilite = (isset($_POST['radSexe'])) ? (int)$_POST['radSexe'] : 3;
        $mails_pourris = isset($_POST['cbSpam']);
    }
    else{

      $pseudo =  $_SESSION['user']['pseudo'];

      // ouverture de la connexion à la base
      $bd = em_bd_connecter();

      $sql = "SELECT utCivilite, utNom, utPrenom, utDateNaissance, utEmail, utMailsPourris FROM utilisateur WHERE utPseudo = '$pseudo'";
      $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
      $tab = mysqli_fetch_assoc($res);

      $civilite = $tab['utCivilite'];
      if ($civilite == 'h') {
        $civilite = 1;
      }
      else {
        $civilite = 2;
      }

      $nom = em_html_proteger_sortie($tab['utNom']);
      $prenom = em_html_proteger_sortie($tab['utPrenom']);
      $email = em_html_proteger_sortie($tab['utEmail']);
      $mails_pourris = $tab['utMailsPourris'];
      $jour = lpl_date_to_day($tab['utDateNaissance']);
      $mois = lpl_date_to_month($tab['utDateNaissance']);
      $annee = lpl_date_to_year($tab['utDateNaissance']);

      // Libération de la mémoire associée au résultat de la requête
      mysqli_free_result($res);
      mysqli_close($bd);
    }

    echo
        '<main>',
        '<section>',
            '<h2>Informations personnelles</h2>',
            '<p>Vous pouvez modifier les informations suivantes</p>',
            '<form action="compte.php" method="post">';

    if ($erreurs) {
        echo '<div class="erreur"><p>Les erreurs suivantes ont été relevées :<ul>';
        foreach ($erreurs as $err) {
            echo '<li>', $err, '</li>';
        }
        echo '</ul><p></div>';
    }

    else if(isset($_POST['btnEnregistrer'])){
      echo '<div class="success"><p>Vos informations ont été mises à jour.<ul>',
          '</ul><p></div>';
    }

    echo '<table>';

    em_aff_ligne_input_radio('Votre civilité :', 'radSexe', array(1 => 'Monsieur', 2 => 'Madame'), $civilite, array('required' => 0));
    em_aff_ligne_input('text', 'Votre nom :', 'nom', $nom, array('required' => 0));
    em_aff_ligne_input('text', 'Votre prénom :', 'prenom', $prenom, array('required' => 0));

    em_aff_ligne_date('Votre date de naissance :', 'naissance', $anneeCourante - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante, $jour, $mois, $annee);

    em_aff_ligne_input('email', 'Votre email :', 'email', $email, array('required' => 0));

    echo    '<tr>', '<td colspan="2">';

    $attributs_checkbox = array();
    if ($mails_pourris){
        // l'attribut checked est un attribut booléen qui n'a pas de valeur
        $attributs_checkbox['checked'] = 0;
    }

    em_aff_input_checkbox('J\'accepte de recevoir des tonnes de mails pourris', 'cbSpam', 1, $attributs_checkbox);

    echo    '</td></tr>',
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnEnregistrer" value="Enregistrer">',
                    '<input type="reset" value="Réinitialiser">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section></main>';
}

//___________________________________________________________________
/**
 * Affichage de la section changement de mdp
 *
 *  @global array    $_POST
 *  @param  array    $erreurs2  Tableau regroupant les erreurs relevées pour les mdp
 */
function lpl_aff_authentification_mdp($erreurs2) {

  echo
      '<main>',
      '<section>',
          '<h2>Authentification</h2>',
          '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',
          '<form action="compte.php" method="post">';

  if ($erreurs2) {
    echo '<div class="erreur"><p>Les erreurs suivantes ont été relevées :<ul>';
    foreach ($erreurs2 as $err) {
      echo '<li>', $err, '</li>';
    }
    echo '</ul><p></div>';
  }
  else if(isset($_POST['btnEnregistrer2'])){
    echo '<div class="success"><p>Le mot de passe à été changé avec succès.<ul>',
          '</ul><p></div>';
  }

  echo '<table>';

  em_aff_ligne_input('password', 'Choisissez un mot de passe :', 'passe1', '', array('required' => 0));
  em_aff_ligne_input('password', 'Répétez le mot de passe :', 'passe2', '', array('required' => 0));

  echo '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="btnEnregistrer2" value="Enregistrer">',
        '</td>',
        '</tr>',
      '</table>',
    '</form>',
    '</section></main>';
  }

//___________________________________________________________________
/**
  *  Traitement d'une demande de modification de mdp.
  *
  *  Si les mdp sont équivalents, un nouvel enregistrement mdp est ajouté dans la table utilisateur.
  *
  *  @global array    $_POST
  *  @global array    $_SESSION
  *  @return array    $erreurs2  Tableau regroupant les erreurs relevées pour les mdp
*/
function lpl_traitement_modif_mdp() {

  $erreurs2 = array();

  // vérification des mots de passe
  $passe1 = trim($_POST['passe1']);
  $passe2 = trim($_POST['passe2']);

  if ($passe1 !== $passe2) {
      $erreurs2[] = 'Les mots de passe doivent être identiques.';
  }

  // si erreurs --> retour
  if (count($erreurs2) > 0) {
      return $erreurs2;   //===> FIN DE LA FONCTION
  }

  $pseudo =  $_SESSION['user']['pseudo'];

  // calcul du hash du mot de passe pour enregistrement dans la base.
  $passe = password_hash($passe1, PASSWORD_DEFAULT);

  // ouverture de la connexion à la base
  $bd = em_bd_connecter();

  $passe = mysqli_real_escape_string($bd, $passe);

  $sql = "UPDATE IGNORE utilisateur SET utPasse = '{$passe}' WHERE utPseudo = '{$pseudo}'";

  mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

  // fermeture de la connexion à la base de données
  mysqli_close($bd);

}

//___________________________________________________________________
/**
  *  Traitement d'une demande de modification des informations personnelles
  *
  *  Si il n'y a aucune erreur de saisie des infos, on fait un nouvel enregistrement dans la bdd de ces infos
  *
  *  @global array    $_POST
  *  @global array    $_SESSION
  *  @return array    $erreurs   Tableau regroupant les erreurs relevées pour les infos perso
*/
function lpl_traitement_enregistrer() {


    if( !em_parametres_controle('post', array('nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 'email', 'btnEnregistrer'), array('cbSpam', 'radSexe'))) {
        em_session_exit();
    }

    $erreurs = array();

    // vérification de la civilité
    if (! isset($_POST['radSexe'])){
        $erreurs[] = 'Vous devez choisir une civilité.';
    }
    else if (! (em_est_entier($_POST['radSexe']) && em_est_entre($_POST['radSexe'], 1, 2))){
        em_session_exit();
    }

    // vérification des noms et prénoms
    $nom = em_html_proteger_sortie(trim($_POST['nom']));
    $prenom = em_html_proteger_sortie(trim($_POST['prenom']));
    eml_verifier_texte($nom, 'Le nom', $erreurs, LMAX_NOM);
    eml_verifier_texte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM);

    // vérification de la date
    if (! (em_est_entier($_POST['naissance_j']) && em_est_entre($_POST['naissance_j'], 1, 31))){
        em_session_exit();
    }

    if (! (em_est_entier($_POST['naissance_m']) && em_est_entre($_POST['naissance_m'], 1, 12))){
        em_session_exit();
    }
    $anneeCourante = (int) date('Y');
    if (! (em_est_entier($_POST['naissance_a']) && em_est_entre($_POST['naissance_a'], $anneeCourante  - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante))){
        em_session_exit();
    }

    $jour = (int)$_POST['naissance_j'];
    $mois = (int)$_POST['naissance_m'];
    $annee = (int)$_POST['naissance_a'];
    if (!checkdate($mois, $jour, $annee)) {
        $erreurs[] = 'La date de naissance n\'est pas valide.';
    }
    else if (mktime(0,0,0,$mois,$jour,$annee+18) > time()) {
        $erreurs[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
    }

    if($mois < 10){
      $mois = '0' . $mois;
    }
    if ($jour < 10) {
        $jour = '0' . $jour;
    }

    // vérification du format de l'adresse email
    $email = em_html_proteger_sortie(trim($_POST['email']));
    if (empty($email)){
        $erreurs[] = 'L\'adresse mail ne doit pas être vide.';
    }
    else if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
        $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
    }

    else if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse mail n\'est pas valide.';
    }

    // vérification si l'utilisateur accepte de recevoir les mails pourris
    if (isset($_POST['cbSpam']) && ! (em_est_entier($_POST['cbSpam']) && $_POST['cbSpam'] == 1)){
        em_session_exit();
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // ouverture de la connexion à la base
    $bd = em_bd_connecter();
    $pseudo =  $_SESSION['user']['pseudo'];

    $emaile = mysqli_real_escape_string($bd, $email);
    $sql = "SELECT utEmail FROM utilisateur WHERE utPseudo != '{$pseudo}'";
    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    while($tab = mysqli_fetch_assoc($res)) {
        if ($tab['utEmail'] == $email){
            $erreurs[] = 'Cette adresse email est déjà inscrite.';
        }
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    $civilite = (int) $_POST['radSexe'];
    //$civilite = $civilite == 1 ? 'h' : 'f';

    $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    $sql = "UPDATE IGNORE utilisateur SET utEmail = '{$emaile}', utNom = '{$nom}', utPrenom = '{$prenom}', utDateNaissance = '{$annee}{$mois}{$jour}', utCivilite = '{$civilite}', utMailsPourris = '{$mailsPourris}' WHERE utPseudo = '{$pseudo}'";

    mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    // redirection sur la page protegee.php
    header('location: ./compte.php');    // TODO : A MODIFIER DANS LE PROJET
    exit(); //===> Fin du script
}

//___________________________________________________________________
/**
 * Affichage des informations d'un rédacteur ou administrateur
 *
 *  @global array    $_POST
 *  @global array    $_SESSION
 */
function lpl_aff_infos_redacteur() {

    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnEnregistrer3'])){
        $bio = em_html_proteger_sortie(trim($_POST['bio']));
        $fonction = em_html_proteger_sortie(trim($_POST['fonction']));
        $cat = trim($_POST['cat']);
        $nomcat1 = '';
        $nomcat2 = '';
        $nomcat3 = '';
        $numcat2 = '';
        $numcat3 = '';
    }
    else{

      $pseudo =  $_SESSION['user']['pseudo'];

      // ouverture de la connexion à la base
      $bd = em_bd_connecter();

      $sql = "SELECT reCategorie, reFonction, reBio FROM redacteur WHERE rePseudo = '$pseudo'";
      $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
      $tab = mysqli_fetch_assoc($res);
      $bio = em_html_proteger_sortie(trim($tab['reBio']));
      $fonction = em_html_proteger_sortie(trim($tab['reFonction']));
      $cat = $tab['reCategorie'];

      if(mysqli_num_rows($res) == FALSE){
        $nomcat1 = 'Rédacteur en chef';
        $nomcat2 = 'Premier violon';
        $nomcat3 = 'Sous-fifre';
        $numcat2 = 2;
        $numcat3 = 3;
      } else {
        $nomcat1 = '';
        $nomcat2 = '';
        $nomcat3 = '';
        $numcat2 = '';
        $numcat3 = '';
      }

      // Libération de la mémoire associée au résultat de la requête
      mysqli_free_result($res);
      mysqli_close($bd);
    }

    if($cat == 1){
      $nomcat1 = "Rédacteur en chef";
      $nomcat2 = "Premier violon";
      $nomcat3 = "Sous-fifre";

      $numcat2 = 2;
      $numcat3 = 3;
    }
    else if($cat == 2){
      $nomcat1 = "Premier violon";
      $nomcat2 =  "Rédacteur en chef";
      $nomcat3 = "Sous-fifre";

      $numcat2 = 1;
      $numcat3 = 3;
    }
    else if($cat == 3){
      $nomcat1 = "Sous-fifre";
      $nomcat2 =  "Rédacteur en chef";
      $nomcat3 = "Premier violon";

      $numcat2 = 1;
      $numcat3 = 2;
    }

    echo
        '<main>',
        '<section>',
            '<h2>Informations rédacteur</h2>',
            '<p>Vous pouvez modifier les informations suivantes</p>',
            '<form action="compte.php" method="post">',
          '<table>',
          '<tr>',
            '<td><label for="bio">Votre biographie :</label></td>',
            '<td><textarea name="bio" value=\'bio\' cols="40" rows=\"7\">',
           $bio, "</textarea></td>",
          '</tr>';

    lp_aff_liste_categorie_redacteur('Catégorie :', $cat, $nomcat1, $nomcat2, $nomcat3, $numcat2, $numcat3);
    em_aff_ligne_input('text', 'Votre fonction :', 'fonction', $fonction, array('required' => 0));

    echo    '</td></tr>',
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnEnregistrer3" value="Enregistrer">',
                    '<input type="reset" value="Réinitialiser">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section></main>';
}

//___________________________________________________________________
/**
  *  Traitement d'une demande de modification des informations du rédacteur
  *
  *  Si il n'y a aucune erreur, on fait un nouvel enregistrement dans la bdd de ces infos
  *
  *  @global array    $_POST
  *  @global array    $_SESSION
*/
function lpl_traitement_enregistrer_infos_redacteur() {

    // ouverture de la connexion à la base
    $bd = em_bd_connecter();

    $pseudo =  $_SESSION['user']['pseudo'];

    if (!em_est_entier($_POST['cat'])){
        em_session_exit();
    }

    $bio = $_POST['bio'];
    $fonction = $_POST['fonction'];
    $categorie = (int) $_POST['cat'];

    $bioe = mysqli_real_escape_string($bd, $bio);
    $fonctione = mysqli_real_escape_string($bd, $fonction);

    $sql = "UPDATE IGNORE redacteur SET reBio = '{$bioe}', reFonction = '{$fonctione}', reCategorie = '{$categorie}' WHERE rePseudo = '{$pseudo}'";

    mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    // redirection sur la page protegee.php
    header('location: ./compte.php');    // DONE
    exit(); //===> Fin du script
}

//___________________________________________________________________
/**
 * Affichage de la partie photo du rédacteur ou administrateur
 *
 *  @global array    $_SESSION
 */
function lpl_aff_photo_redacteur() {

    $pseudo =  $_SESSION['user']['pseudo'];
    $img = "../upload/" . $pseudo . ".jpg";

    if(empty($img)){
      $img = "../images/" . "anonyme.jpg";
    }

    echo
        '<main>',
        '<section>',
            '<h2>Photo de rédacteur</h2>',
            '<p>Vous pouvez modifier votre photo</p>',
            '<div class="photo_redacteur">',
            '<img src="', $img ,'" width="150" height="200"></div>',
            '<form action="compte.php" method="post" enctype="multipart/form-data">',
            '<table>',
              '<tr><td><label for="bio">Choisissez une nouvelle photo :</label></td>',
                '<td>',
                  '<input type="file" name="photo">',
                '</td></tr>',
              '<tr>',
                '<td colspan="2">',
                  '<input type="submit" name="btnEnregistrer4" value="Enregistrer">',
                '</td>',
              '</tr>',
            '</table>',
            '</form>',
        '</section></main>';
}

//___________________________________________________________________
/**
  * On récupère la photo uploader et on l'ajoute à la place de l'ancienne photo du rédacteur
  * dans le dossier upload.
  *
  *  @global array    $_FILES
  *  @global array    $_SESSION
*/
function lpl_traitement_enregistrer_photo_redacteur() {

  if(isset($_FILES['photo'])){

    $pseudo =  $_SESSION['user']['pseudo'];
    $tmp = $_FILES['photo']['tmp_name'];
    $nom = $_FILES['photo']['name'];
    $dossier = '../upload/';
    move_uploaded_file($tmp, $dossier . $pseudo . ".jpg");

    // redirection sur la page protegee.php
    header('location: ./compte.php');    // TODO : A MODIFIER DANS LE PROJET
    exit(); //===> Fin du script
  }
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format MM
 *
 *  @param  int     $date   la date à convertir.
 *  @return string  $month  la chaîne qui représente le mois
 */
function lpl_date_to_month($date) {

    $mois = substr($date, -4, 2);
    return $mois;
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format JJ
 *
 *  @param  int     $date   la date à afficher.
 *  @return string  $jour   la chaîne qui représente le jour
 */
function lpl_date_to_day($date) {

    $jour = substr($date, -2, 2);

    return $jour;
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format AAAA
 *
 *  @param  int     $date   la date à afficher.
 *  @return string  $annee  la chaîne qui représente l'année
 */
function lpl_date_to_year($date) {

    $annee = substr($date, 0, 4);

    return $annee;
}

?>
