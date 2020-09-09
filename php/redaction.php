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
em_aff_entete('Rédaction', 'Rédaction');


tcl_aff_statiquemot();

tcl_aff_redaction();

tcl_aff_statiquerecrute();

echo '</main>';


// pied de page
em_aff_pied();

// fin du script
ob_end_flush();

/**
 * Affichage statique de la partie "le mot de la rédaction"
 */
function tcl_aff_statiquemot()
{
    echo '<main>',
            '<section>',
            '<h2>',
                'Le mot de la rédaction',
            '</h2>',
            '<p>',
                'Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé en 2019 ce site pour répondre à un ',
                'réel besoin : celui de fournir une information fiable et précise sur la vie de la ', 
                '<abbr title="Licence Informatique">',
                    'L-INFO',
                '</abbr>',
                ' de l\'<a href="http://www.univ-fcomte.fr" target="_blank">Université de Franche-Comté</a>.</p>',
            '<p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de la Gazette de L-INFO. </p>',
            '</section>';
}

/**
 * Affichage statique de la partie "La Gazette de L-INFO recrute !"
 */
function tcl_aff_statiquerecrute()
{
    echo '<section>',
        '<h2>La Gazette de L-INFO recrute !</h2>',
            '<p>',
                'Si vous souhaitez vous aussi faire partie de notre team, rien de plus simple. Envoyez-nous un mail grâce au lien dans le menu de navigation, et rejoignez l\'équipe.',
            '</p>',
        '</section>';
}

/**
 * Affiche les informations concernant un rédacteur,
 * en prenant en compte les paramètres afin de générer
 * le bon code html
 *
 * @param 	tableau 	$tab 	tableau contenant le résultat de la requête sql récupérant les infos du rédacteur en question
 * @param 	String  	$h2 	nouveau titre de la section
 * @param 	boolean 	$close	description2
 */
function tcl_aff_red($tab, $h2="", $close=false)
{

    $indice = (int) $tab['reCategorie'];
    $pseudo = em_html_proteger_sortie($tab['rePseudo']);
    $prenomNom = em_html_proteger_sortie($tab['utPrenom']) . " " . em_html_proteger_sortie($tab['utNom']);

    if($close)
    {
        echo '</section>';
    }

    if($h2 != "")
    {
        echo '<section>',
            '<h2>', $h2 ,'</h2>';
    }

    echo '<article class="redacteur" id="', $pseudo , '">';

    $img = "../upload/" . $pseudo . ".jpg";

    if(file_exists($img))
    {
        echo '<img src="', $img ,'" width="150" height="200" alt="', $prenomNom ,'">';
    }
    else
    {
        echo '<img src="../images/anonyme.jpg" width="150" height="200" alt="', $prenomNom ,'">';
    }

    echo '<h3>', $prenomNom ,'</h3>';

    if(($fctn = em_html_proteger_sortie($tab['reFonction'])) != "")
    {
        echo '<h4>', $fctn ,'</h4>';
    }

    echo tc_trad_bbcode(em_html_proteger_sortie($tab['reBio'])),
        '</article>';

}

/**
 * Fonction principale de l'affichage de 
 * la rédaction. Cette dernière fera appelle
 * à la fonction précédente en changeant les
 * paramètres
 */
function tcl_aff_redaction()
{
    $bd = em_bd_connecter();

    $sql = "SELECT redacteur.*, utilisateur.utPseudo, utilisateur.utStatut, utilisateur.utPrenom, utilisateur.utNom
            FROM redacteur, utilisateur
            WHERE utilisateur.utStatut > 0
            AND utilisateur.utPseudo = redacteur.rePseudo  
            AND redacteur.reBio != \"\"
            ORDER BY `redacteur`.`reCategorie` ASC";

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    $actuel = 0;

    while($tab = mysqli_fetch_assoc($res)) 
    {
        
        $indice = (int) $tab['reCategorie'];
        $pseudo = em_html_proteger_sortie($tab['rePseudo']);
        $prenomNom = em_html_proteger_sortie($tab['utPrenom']) . " " . em_html_proteger_sortie($tab['utNom']);

        if($indice == 1)
        {
            if($actuel == 1)
            {
                tcl_aff_red($tab);
            }
            else
            {
                tcl_aff_red($tab, "Notre rédacteur en chef");
                $actuel = 1;
            }
        }
        else if($indice == 2)
        {
            if($actuel == 2)
            {
                tcl_aff_red($tab);
            }
            else
            {
                tcl_aff_red($tab, "Nos premiers violons", true);
                $actuel = 2;
            }
        }
        else if($indice == 3)
        {
            if($actuel == 3)
            {
                tcl_aff_red($tab);
            }
            else
            {
                tcl_aff_red($tab, "Nos sous-fifres", true);
                $actuel = 3;
            }
        }
    }

    echo '</section>';
}

?>