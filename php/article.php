<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();


// si il y a une autre clé que id dans $_GET, piratage ?
// => l'utilisateur est redirigé vers index.php
if (!em_parametres_controle('get', array(), array('id'))) {
    header('Location: ../index.php');
    exit;
}

// affichage de l'entête
em_aff_entete('L\'actu', 'L\'actu');

// affichage du contenu (article + commentaires)
eml_aff_article();

// pied de page
em_aff_pied();

// fin du script
ob_end_flush();


/**
 * Affichage de l'article et de ses commentaires
 */
function eml_aff_article() {
    
    // vérification du format du paramètre dans l'URL
    if (!isset($_GET['id'])) {
        eml_aff_erreur ('Identifiant d\'article non fourni.');
        return;     // ==> fin de la fonction
    }
        
    if (!em_est_entier($_GET['id']) || $_GET['id'] <= 0) {
        eml_aff_erreur ('Identifiant d\'article invalide.');
        return;     // ==> fin de la fonction
    }
    $id = (int)$_GET['id'];
    
    // ouverture de la connexion à la base de données
    $bd = em_bd_connecter();
    
    // Récupération de l'article, des informations sur son auteur (y compris ses éentuelles infos renseignées dans la table 'redacteur'),
    // de ses éventuelles commentaires
    $sql = "SELECT *  
            FROM ((article INNER JOIN utilisateur ON arAuteur = utPseudo)
            LEFT OUTER JOIN redacteur ON utPseudo = rePseudo)
            LEFT OUTER JOIN commentaire ON arID = coArticle
            WHERE arID = {$id}
            ORDER BY coDate DESC, coID DESC";

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    // pas d'articles --> fin de la fonction
    if (mysqli_num_rows($res) == 0) {
        eml_aff_erreur ('Identifiant d\'article non reconnu.');
        mysqli_free_result($res);
        mysqli_close($bd);
        return;         // ==> fin de la fonction
    }


    // ---------------- GENERATION DE L'ARTICLE ------------------

    // affichage de l'article et des commentaires associés
    echo '<main>';

    // si l'utilisateur est connecté
    if(isset($_SESSION['user']))
    {
        $pseudo = $_SESSION['user']['pseudo'];

        // On vérifie si l'utilisateur est l'auteur de l'article afin de pouvoir lui proposer de modifier ou supprimer son article 
        if(tc_check_auteur($pseudo, $id, $bd))
        {
            echo '<article>',
                '<p>', 'Vous êtes l\'auteur de cet article, ', 
                '<a href="edition.php?id=', $id, '">cliquez ici pour le modifier ou le supprimer</a>',
                '</p>',
                '</article>';
        }

    }
    
    $tab = mysqli_fetch_assoc($res);

    // Mise en forme du prénom et du nom de l'auteur pour affichage dans le pied du texte de l'article
    // Par exemple, pour 'johnny' 'bigOUde', ça donne 'J. Bigoude'
    // A faire avant la protection avec htmlentities() à cause des éventuels accents
    $auteur = eml_mb_ucfirst_lcremainder(mb_substr($tab['utPrenom'], 0, 1, 'UTF-8')) . '. ' . eml_mb_ucfirst_lcremainder($tab['utNom']);
    
    // protection contre les attaques XSS
    $auteur = em_html_proteger_sortie($auteur);

    // protection contre les attaques XSS
    $tab = em_html_proteger_sortie($tab);
    
    $imgFile = "../upload/{$id}.jpg";
    
    
    // génération du bloc <article>
    echo '<article class="art_redac">', 
            '<h3>', html_entity_decode($tab['arTitre']), '</h3>',
            ((file_exists($imgFile)) ? "<img src='{$imgFile}' alt=\"Photo d\'illustration | {$tab['arTitre']}\">" : ''),
            '<p>',
            tc_trad_bbcode($tab['arTexte']),
            //'</p>',        
            '<footer>Par ',
            // si l'auteur a encore le droit de rédacteur et si il a enregistré des informations dans la table redacteur
            // on affiche un lien vers sa présentation sur la page redaction.php, 
            // sinon on affiche uniquement $auteur
            ((isset($tab['rePseudo']) && ($tab['utStatut'] == 1 || $tab['utStatut'] == 3)) ?
            "<a href='../php/redaction.php#{$tab['utPseudo']}'>$auteur</a>" : $auteur), 
            '. Publié le ', eml_date_to_string($tab['arDatePublication']);

    // ajout dans le pied d'article d'une éventuelle date de modification
    if (isset($tab['arDateModification'])) {
        echo ', modifié le '. eml_date_to_string($tab['arDateModification']);
    }
    
    // fin du bloc <article>
    echo '</footer>',                
        '</article>';

    //pour accéder une seconde fois au premier enregistrement de la sélection
    mysqli_data_seek($res, 0); 

    // Génération du début de la zone de commentaires
    echo '<section>',
            '<h2>Réactions</h2>';    
    
    // s'il existe des commentaires, on les affiche un par un.
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($res)) 
        {
            // Si l'utilisateur est connecté et qu'il est l'auteur de ce commentaire il peut le supprimer en passant sa souris dessus
            if(isset($_SESSION['user']) && (em_html_proteger_sortie($tab['coAuteur']) == $_SESSION['user']['pseudo']))
            {
                echo '<li class="suppcomm">',
                        '<form action="#" method="post">',
                    '<input type="submit" name="BtnSupComm" value="Supprimer ce commentaire">',
                    '</form>';
                // Si l'utilisateur a appuyé sur le bouton pour supprimer le commentaire alors on le retire 
                if(isset($_POST['BtnSupComm']))
                {
                    tc_remove_comment($tab['coID'], $bd);
                    header("Refresh: 0");
                }
            }
            else
            {
                echo '<li>';
            }
            // on affiche le commentaire normalement
            echo 'Commentaire de <strong>', em_html_proteger_sortie($tab['coAuteur']), '</strong>, le ',
                    eml_date_to_string($tab['coDate']),
                    '<blockquote>', tc_trad_bbcode(em_html_proteger_sortie($tab['coTexte'])), '</blockquote>',
                '</li>';
        }
        echo '</ul>';
    }
    // sinon on indique qu'il n'y a pas de commentaires
    else
    {
        echo '<p>Il n\'y a pas de commentaires à cet article. </p>';  
    }
    
    // libération des ressources
    mysqli_free_result($res);
    
    // fermeture de la connexion à la base de données
    
    // Si l'utilisateur est connecté alors on lui propose de poster un commentaire
    if(isset($_SESSION['user']))
    {
        echo '<fieldset>',
            '<legend>',
            'Ajoutez un commentaire',
            '</legend>',
            '<form action="#" method="post">',
            '<textarea name="commentaire" rows="15" cols="90">',
            '</textarea><br>',
            '<input type="submit" name="BtnComm" value="Publier ce commentaire">',
            '</form>',
            '</fieldset>';
        
        if(isset($_POST['BtnComm'])) // Si l'utilisateur a décidé de poster un commentaire, on l'ajoute tout en vérifiant qu'il y a bien quelque chose d'écrit
        {
            if($_POST['commentaire'] != "")
            {
                tc_add_comment(htmlentities($_POST['commentaire']), $_SESSION['user']['pseudo'] , $id, $bd);
                header("Refresh: 0");
            }
        }
    }
    else
    {
        // Si il n'est pas connecté, alors on lui propose de se connecter ou bien de s'inscrire
        echo '<p>',
        '<a href="../php/connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !',
        '</p>';
    }

    echo '</section>',
         '</main>';

    mysqli_close($bd);
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format JJ mois AAAA à HHhMM
 *
 *  @param  int     $date   la date à afficher. 
 *  @return string          la chaîne qui reprsente la date
 */
function eml_date_to_string($date) {
    // les champs date (coDate, arDatePublication, arDateModification) sont de type BIGINT dans la base de données
    // donc pas besoin de les protéger avec htmlentities()
    
    // si un article a été publié avant l'an 1000, ça marche encore :-)
    $min = substr($date, -2);
    $heure = (int)substr($date, -4, 2); //conversion en int pour supprimer le 0 de '07' pax exemple
    $jour = (int)substr($date, -6, 2);
    $mois = substr($date, -8, 2);
    $annee = substr($date, 0, -8);
    
    $month = em_get_tableau_mois();    
    
    return $jour. ' '. mb_strtolower($month[$mois - 1], 'UTF-8'). ' '. $annee . ' à ' . $heure . 'h' . $min;
    // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strtolower.php
}

//___________________________________________________________________
/**
 * Renvoie une copie de la chaîne UTF8 transmise en paramètre après avoir mis sa
 * première lettre en majuscule et toutes les suivantes en minuscule
 *
 * @param  string   $str    la chaîne à transformer
 * @return string           la chaîne résultat
 */
function eml_mb_ucfirst_lcremainder($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $fc = mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'));
    return $fc.mb_substr($str, 1, mb_strlen($str), 'UTF-8');
}

//_______________________________________________________________
/**
 *  Affchage d'un message d'erreur dans une zone dédiée de la page.
 *  @param  String  $msg    le message d'erreur à afficher.
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



?>
