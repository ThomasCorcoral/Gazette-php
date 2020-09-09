<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// génération de la page
em_aff_debut('Inscription', '..', '');

echo    '<h2>Réception du formulaire<br>Inscription utilisateur</h2>';

/*
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent une redirection de l'utilisateur vers la page index.php sauf les éventuelles suppressions des attributs required 
 * car l'attribut required est une nouveauté apparue dans la version HTML5 et nous souhaitons que l'application fonctionne également 
 * correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 *
 */
if( !em_parametres_controle('post', array('pseudo', 'nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 
                                            'passe1', 'passe2', 'email', 'btnInscription'), array('cbCGU', 'cbSpam', 'radSexe'))) {
    header('Location: ../index.php');
    exit();     
}

$erreurs = array();

// vérification du pseudo
$pseudo = trim($_POST['pseudo']);
if (!preg_match('/^[0-9a-z]{'. LMIN_PSEUDO . ',' . LMAX_PSEUDO . '}$/',$pseudo)) { 
    $erreurs[] = 'Le pseudo doit contenir entre ' . LMIN_PSEUDO . ' et ' . LMAX_PSEUDO . ' lettres minuscules (sans accent) ou chiffres.';
}

// vérification de la civilité
if (! isset($_POST['radSexe'])){
    $erreurs[] = 'Vous devez choisir une civilité.';
}
else if (! (em_est_entier($_POST['radSexe']) && em_est_entre($_POST['radSexe'], 1, 2))){
    header('Location: ../index.php');
    exit();  
}

// vérification des noms et prénoms
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
eml_verifier_texte($nom, 'Le nom', $erreurs, LMAX_NOM);
eml_verifier_texte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM);

// vérification de la date
if (! (em_est_entier($_POST['naissance_j']) && em_est_entre($_POST['naissance_j'], 1, 31))){
    header('Location: ../index.php');
    exit();   
}

if (! (em_est_entier($_POST['naissance_m']) && em_est_entre($_POST['naissance_m'], 1, 12))){
    header('Location: ../index.php');
    exit();
}
$anneeCourante = (int) date('Y');
if (! (em_est_entier($_POST['naissance_a']) && em_est_entre($_POST['naissance_a'], $anneeCourante  - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante))){
    header('Location: ../index.php');
    exit(); 
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

// vérification du format de l'adresse email
$email = trim($_POST['email']);
if (empty($email)){
    $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
}
else if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
    $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
}
// la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
// est moins forte que celle faite ci-dessous avec la fonction filter_var()
// Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
// celle faite ci-dessous
else if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = 'L\'adresse mail n\'est pas valide.';
}

// vérification des mots de passe
$passe1 = trim($_POST['passe1']);
$passe2 = trim($_POST['passe2']);
if (empty($passe1) || empty($passe2)) {
    $erreurs[] = 'Les mots de passe ne doivent pas être vides.';
}
else if ($passe1 !== $passe2) {
    $erreurs[] = 'Les mots de passe doivent être identiques.';
}

// vérification de la valeur de l'élément cbCGU
if (! isset($_POST['cbCGU'])){
    $erreurs[] = 'Vous devez accepter les conditions générales d\'utilisation.';
}
else if (! (em_est_entier($_POST['cbCGU']) && $_POST['cbCGU'] == 1)){
    header('Location: ../index.php');
    exit(); 
}

// vérification si l'utilisateur accepte de recevoir les mails pourris
if (isset($_POST['cbSpam']) && ! (em_est_entier($_POST['cbSpam']) && $_POST['cbSpam'] == 1)){
    header('Location: ../index.php');
    exit();
}


// si erreurs
if (count($erreurs) > 0) {
    eml_aff_erreurs($erreurs);
    exit('</body></html>');     //==> FIN DU SCRIPT
}

// on vérifie si le pseudo et l'adresse mail ne sont pas encore utilisés que si toutes les autres vérifications
// réussissent car ces 2 dernières vérifications coûtent un bras !

// ouverture de la connexion à la base 
$bd = em_bd_connecter();

// vérification de l'existence du pseudo ou de l'email
$pseudoe = mysqli_real_escape_string($bd, $pseudo); // fait par principe, mais inutile ici car on a déjà vérifié que le pseudo
                                        // ne contenait que des caractères alphanumériques
$emaile = mysqli_real_escape_string($bd, $email);
$sql = "SELECT utPseudo, utEmail FROM utilisateur WHERE utPseudo = '{$pseudoe}' OR utEmail = '{$emaile}'";
$res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

while($tab = mysqli_fetch_assoc($res)) {
    if ($tab['utPseudo'] == $pseudo){
        $erreurs[] = 'Le pseudo choisi existe déjà.';
    }
    if ($tab['utEmail'] == $email){
        $erreurs[] = 'Cette adresse email est déjà inscrite.';
    }
}

// Libération de la mémoire associée au résultat de la requête
mysqli_free_result($res);

// fermeture de la connexion à la base de données
mysqli_close($bd);

// si erreurs
if (count($erreurs) > 0) {
    eml_aff_erreurs($erreurs);
    exit('</body></html>');     //==> FIN DU SCRIPT
}


echo '<p>Aucune erreur de saisie</p>';

echo '</body></html>';

ob_end_flush();


//___________________________________________________________________
/**
 * Vérification des champs nom et prénom
 *
 * @param  string       $texte champ à vérifier
 * @param  string       $nom chaîne à ajouter dans celle qui décrit l'erreur
 * @param  array        $erreurs tableau dans lequel les erreurs sont ajoutées
 * @param  int          $long longueur maximale du champ correspondant dans la base de données
 */
function eml_verifier_texte($texte, $nom, &$erreurs, $long = -1){
    mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
    if (empty($texte)){
        $erreurs[] = "$nom ne doit pas être vide.";
    }
    else if(strip_tags($texte) != $texte){
        $erreurs[] = "$nom ne doit pas contenir de tags HTML";
    }
    elseif ($long > 0 && mb_strlen($texte, 'UTF-8') > $long){
        // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strlen.php
        $erreurs[] = "$nom ne peut pas dépasser $long caractères";
    }
    elseif(!mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $texte)){
        $erreurs[] = "$nom contient des caractères non autorisés";
    }
}

//___________________________________________________________________
/**
 * Affichage des erreurs contenues dans le tableau reçu en paramètre
 *
 * @param  array        $erreurs tableau de string
 */
function eml_aff_erreurs($erreurs){
    echo '<p>Les erreurs suivantes ont été relevées lors de votre inscription :</p><ul>';
        foreach ($erreurs as $err) {
            echo '<li>', $err, '</li>';   
        }
        echo '</ul>';
}

?>
