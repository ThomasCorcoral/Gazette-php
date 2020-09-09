<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *        à l'application Gazette de L-Info              *
 *********************************************************/


/** Constantes : les paramètres de connexion au serveur MySQL */
define ('BD_NAME', 'gazette_bd');
define ('BD_USER', 'gazette_user');
define ('BD_PASS', 'gazette_pass');
define ('BD_SERVER', 'localhost');


define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 20);

define('LMAX_NOM', 50);
define('LMAX_PRENOM', 60);

define('LMAX_EMAIL', 255);

define('NB_ANNEE_DATE_NAISSANCE', 100);

//_______________________________________________________________
/**
 *  Affichage du début de la page (jusqu'au tag ouvrant de l'élément body)
 *
 *
 *  @param  string  $title      Le titre de la page (<head>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 *  @param  array   $css        Le nom de la feuille de style à inclure
 */
function em_aff_debut($title = '', $prefix='..', $css = 'gazette.css') {

    echo
        '<!doctype html>',
        '<html lang="fr">',
            '<head>',
                '<meta charset="UTF-8">',
                '<title>La gazette de L-INFO', ($title != '') ? ' | ' : '', $title, '</title>',
                $css != '' ? "<link rel='stylesheet' type='text/css' href='{$prefix}/styles/{$css}'>" : '',
            '</head>',
            '<body>';
}



//_______________________________________________________________
/**
 *  Affiche le code du menu de navigation.
 *
 *  @param  string  $pseudo     chaine vide quand l'utilisateur n'est pas authentifié
 *  @param  array   $droits     Droits rédacteur à l'indice 0, et administrateur à l'indice 1
 *  @param  String  $prefix     le préfix du chemin relatif vers la racine du site
 */
function em_aff_menu($pseudo='', $droits = array(false, false), $prefix = '..') {

    echo '<nav><ul>',
            '<li><a href="', $prefix, '/index.php">Accueil</a></li>',
            '<li><a href="', $prefix, '/php/actus.php">Toute l\'actu</a></li>',
            '<li><a href="', $prefix, '/php/recherche.php">Recherche</a></li>',
            '<li><a href="', $prefix, '/php/redaction.php">La rédac\'</a></li>',
            '<li>';

    // dernier item du menu ("se connecter" ou sous-menu)
    if ($pseudo) {
        echo '<a href="#">', $pseudo, '</a>',
                '<ul>',
                    '<li><a href="', $prefix, '/php/compte.php">Mon profil</a></li>',
                    $droits[0] ? "<li><a href=\"{$prefix}/php/nouveau.php\">Nouvel article</a></li>" : '',
                    $droits[1] ? "<li><a href=\"{$prefix}/php/administration.php\">Administration</a></li>" : '',
                    '<li><a href="', $prefix, '/php/deconnexion.php">Se déconnecter</a></li>',
                '</ul>';
    }
    else {
        echo '<a href="', $prefix, '/php/connexion.php">Se connecter</a>';
    }

    echo '</li></ul></nav>';
}

//_______________________________________________________________
/**
 *  Affichage de l'élément header
 *
 *  @param  string  $h1         Le titre dans le bandeau (<header>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 */
function em_aff_header($h1, $prefix='..'){
    echo '<header>',
            '<img src="', $prefix, '/images/titre.png" alt="La gazette de L-INFO" width="780" height="83">',
            '<h1>', $h1, '</h1>',
        '</header>';
}

//_______________________________________________________________
/**
 *  Affichage du début de la page (de l'élément doctype jusqu'à l'élément header inclus)
 *
 *  Affiche notamment le menu de navigation en utilisant $_SESSION
 *
 *  @param  string  $h1         Le titre dans le bandeau (<header>)
 *  @param  string  $title      Le titre de la page (<head>)
 *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
 *  @param  array   $css        Le nom de la feuille de style à inclure
 *  @global array   $_SESSION
 */
function em_aff_entete($h1, $title='', $prefix='..', $css = 'gazette.css')
{
    em_aff_debut($title, $prefix, $css);
    $pseudo = '';
    $droits = array(false, false);
    if (isset($_SESSION['user'])){
        $pseudo = $_SESSION['user']['pseudo'];
        $droits = array($_SESSION['user']['redacteur'], $_SESSION['user']['administrateur']);
    }
    em_aff_menu($pseudo, $droits, $prefix);
    em_aff_header($h1, $prefix);
}

//_______________________________________________________________
/**
 *  Affichage du pied de page du document.
 */
function em_aff_pied() {
    echo    '<footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer>',
        '</body>',
    '</html>';
}




//_______________________________________________________________
/**
 *  Génère l'URL de l'image d'illustration d'un article en fonction de son ID
 *  - si l'image ou la photo existe dans le répertoire /upload, on renvoie son url
 *  - sinon on renvoie l'url d'une image générique
 *  @param  int     $id         l'identifiant de l'article
 *  @param  String  $prefix     le chemin relatif vers la racine du site
 */
function em_url_image_illustration($id, $prefix='..') {

    $url = "{$prefix}/upload/{$id}.jpg";

    if (! file_exists($url)) {
        return "{$prefix}/upload/none.jpg" ;
    }

    return $url;
}

//_______________________________________________________________
/**
* Vérifie si l'utilisateur est authentifié.
*
* Termine la session et redirige l'utilisateur
* sur la page connexion.php s'il n'est pas authentifié.
*
* @global array   $_SESSION
*/
function em_verifie_authentification() {
    if (! isset($_SESSION['user'])) {
        em_session_exit('./connexion.php');
    }
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour
 * stocker par exemple l'adresse IP, etc.
 *
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function em_session_exit($page = '../index.php') {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(),
            '',
            time() - 86400,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}

/**
 * Renvoie true si l'utilisateur est bien l'auteur
 * de l'article représenté par le parametre $id
 *
 * @param 	String 	$pseudo 	pseudo de l'utilisateur
 * @param 	int 	$id         id de l'article à vérifier
 * @param   objet   $bd         Connecteur sur la bd ouverte
 * @return 	boolean 	true si l'utilisateur est l'auteur et false sinon
 */
function tc_check_auteur($pseudo, $id, $bd)
{
    $sql = "SELECT arID, arAuteur FROM `article`
            WHERE (arAuteur = '$pseudo')
            AND (arID = $id)";

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    $ok = mysqli_num_rows($res);

    mysqli_free_result($res);

    return ($ok != 0);
}

/**
 * Fonction qui ajoute un commentaire
 *
 * @param 	String 	$com     	    commentaire à ajouter
 * @param 	String 	$auteur_come    Pseudo de l'utilisateur qui a écrit le commentaire
 * @param   int     &id             Id de l'aticle rattaché au commentaire
 * @param   objet   $bd             Connecteur sur la bd ouverte
 */
function tc_add_comment($com, $auteur_com, $id, $bd)
{
    //Récupération du dernier id de commentaire
    $sql = 'SELECT coID FROM commentaire ORDER BY coID DESC LIMIT 1';

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    $id_co = (int) mysqli_fetch_assoc($res)['coID'] +1 ;

    mysqli_free_result($res);

    $annee = date(Y);
    $mois = date(m);
    $jour = date(d);
    $heure = date(H);
    $minute = date(i);

    $sql = "INSERT INTO `commentaire` (`coID`, `coAuteur`, `coTexte`, `coDate`, `coArticle`)
            VALUES ('{$id_co}', '{$auteur_com}', '{$com}', '{$annee}{$mois}{$jour}{$heure}{$minute}', '{$id}')";

    mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
}

/**
 * Supprime le commentaire ayant pour id celui entré en paramètre
 *
 * @param 	int 	$id         id de l'article à vérifier
 * @param   objet   $bd         Connecteur sur la bd ouverte
 */
function tc_remove_comment($id, $bd)
{
    $sql = "DELETE FROM commentaire WHERE coID = $id";

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
}


/**
 * Renvoie le plus grand id d'article + 1 pour pouvoir
 * ajouter un nouvel article
 *
 * @param   objet   $bd         Connecteur sur la bd ouverte
 */
function tc_get_arid($bd)
{
    $sql = 'SELECT arID FROM article ORDER BY arID DESC LIMIT 1';

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    $arid = (int) mysqli_fetch_assoc($res)['arID'] +1 ;

    mysqli_free_result($res);

    return $arid;
}

/**
 * Recupère l'url de la balise bbcode [a:url]
 *
 * @param 	String 	$str 	balise bbcode qui contient l'url à extraire
 * @return 	String   	    url extraite
 */
function tc_get_url($str)
{
    $i = 1; // car on sait que $str[0] == "[" et sinon on aura des problèmes avec l'emplacement $i-1
    $res = "";
    while($str[$i] != "]")
    {
        if($res != "" || $str[$i-1] == ":") // On attend les ':'
        {
            $res = $res . $str[$i];
        }
        $i++;
    }
    return $res;
}

/**
 * Recupère le nombre contenue dans la balise bbcode
 *
 * @param 	String 	$str 	balise bbcode qui contient le nombre à extraire
 * @return 	String   	    nombre extrait
 */
function tc_get_nb($str)
{
    if($str[2] == "x")
    {
        $i = 3;
    }
    else
    {
        $i = 2;
    }
    $res = "";
    while($str[$i] != "]")
    {
        $res = $res . $str[$i];
        $i++;
    }
    return $res;
}

/**
 * Renvoie le code html de la frame a ajouter, avec
 * ou bien sans legende
 *
 * @param 	String 	$str 	balise bbcode qui contient les informations
 * @return 	String   	    code html prêt à l'emploi
 */
function tc_get_iframe($str)
{
    // initialisation des variables qui vont contenir les différentes informations
    $ytb_check = "";    // vérification de la balise
    $w = "";            // width / largeur
    $h = "";            // height / hauteur
    $url = "";
    $leg = "";          // legende

    $i = 1;
    $cmpt = 0;

    while($str[$i] != "]")
    {

        if((($str[$i] == ':' && ($str[$i-1] != 'p' && $str[$i-1] != 's'))  || $str[$i] == ' ') && $cmpt < 4 )
        {
            $cmpt++;
        }
        else
        {
            switch($cmpt)
            {
                case 0 : $ytb_check = $ytb_check . $str[$i];
                    break;
                case 1 : $w = $w . $str[$i];
                    break;
                case 2 : $h = $h . $str[$i];
                    break;
                case 3 : $url = $url . $str[$i];
                    break;
                case 4 : $leg = $leg . $str[$i];
                    break;
            }
        }
        $i++;
    }
    if($ytb_check != "youtube"){return $str;}
    if($leg != "")
    {
        //return avec legende
        return "<figure><iframe width='" . $w ."' height='" . $h . "' src='" . $url . "' allowfullscreen></iframe><figcaption>" . $leg . "</figcaption></figure>" ;
    }
    else
    {
        //return uniquement url
        return "<iframe width='" . $w . "' height='" . $h . "' src='" . $url . "' allowfullscreen></iframe>" ;
    }

}

/**
 * renvoie la nouvelle chaine en code html. Elle
 * traduit le BBcode
 *
 * @param 	String 	$str     	chaine à traduire
 *
 * @return 	String   	chaine traduite en html si le BBcode était correctement rédigé
 */

function tc_check_bbcode($str)
{
      if($str == "[p]"){return "<p>";}
      if($str == "[/p]"){return "</p>";}
      if($str == "[gras]"){return "<strong>";}
      if($str == "[/gras]"){return "</strong>";}
      if($str == "[it]"){return "<em>";}
      if($str == "[/it]"){return "</em>";}
      if($str == "[citation]"){return "<blockquote>";}
      if($str == "[/citation]"){return "</blockquote>";}
      if($str == "[liste]"){return "<ul>";}
      if($str == "[/liste]"){return "</ul>";}
      if($str == "[item]"){return "<li>";}
      if($str == "[/item]"){return "</li>";}
      if($str == "[br]"){return "<br>";}
      if($str[1] == 'a' && $str[2] == ':'){return '<a href="' . tc_get_url($str) . '">';}
      if($str == "[/a]"){return "</a>";}

    if($str[1] == '#')
    {
        if($str[2] == 'x')
        {
            return '&#x' . tc_get_nb($str);
        }
        else
        {
            return '&#' . tc_get_nb($str);
        }
    }
    if(strpos($str, "youtube"))
    {
        return tc_get_iframe($str);
    }
    return $str;
}

/**
 * Fonction qui sera appelé lors de la génération. Elle va recevoir
 * la chaine originale à traduire et l'analyser. Elle va ensuite
 * appeler la fonction de traduction quand il le faut
 *
 * @param 	String 	$str     	texte à traduire
 */
function tc_trad_bbcode($str)
{
    $size = strlen($str);

    $i = 0;
    $copy = "";
    $mem = "";

    for($i = 0; $i < $size; $i++)
    {
        if($str[$i] == "[")
        {
            $copy = $copy . $mem;
            $mem = $str[$i];
        }
        else if($str[$i] == "]")
        {
            $mem = $mem . $str[$i];
            $copy = $copy . tc_check_bbcode($mem);
            $mem = "";
        }
        else if($mem != "")
        {
            $mem = $mem . $str[$i];
        }
        else
        {
            $copy = $copy . $str[$i];
        }
    }
    if($mem != "") // Si l'utilisateur écris quelque chose du genre [ blablabla, le programme ne va pas détecter la fin de crochet. Il faut donc rejouter la mémoire.
    {
        $copy = $copy . $mem;
        $mem = "";
    }

    echo $copy;
}

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

function lp_aff_liste_categorie_redacteur($name, $cat, $nomcat1, $nomcat2, $nomcat3, $numcat2, $numcat3) {
    echo '<tr>', '<td>', $name, '</td>', '<td>';
    echo
          '<select name="cat" id="cat">',
              "<option value=$cat>$nomcat1</option>",
              "<option value=$numcat2>$nomcat2</option>",
              "<option value=$numcat3>$nomcat3</option>",
          '</select>';
    echo '</td>', '</tr>';
}

function lp_aff_liste_statut_utilisateur($name, $nomstatut, $statut, $nomstatut0, $nomstatut1, $nomstatut2, $nomstatut3, $numstatut1, $numstatut2, $numstatut3) {
    echo '<tr>', '<td>', $name, '</td>', '<td>';
    echo
          "<select name=$nomstatut id=$nomstatut>",
              "<option value=$statut>$nomstatut0</option>",
              "<option value=$numstatut1>$nomstatut1</option>",
              "<option value=$numstatut2>$nomstatut2</option>",
              "<option value=$numstatut3>$nomstatut3</option>",
          '</select>';
    echo '</td>', '</tr>';
}


?>
