<?php

require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// génération de la page
em_aff_entete('Recherche', 'Recherche');

// affichage de la page de recherche
tcl_aff_research();

em_aff_pied();

ob_end_flush(); //FIN DU SCRIPT



/**
 * Fonction qui permet l'affichage de la page de recherche.
 * 
 * Cette fonction va tout dabord vérifier si une recherche a été effectuée
 * En suite elle stock cette recherche dans une variable. Elle affiche ensuite la page
 * Et enfin elle fait appelle a la fonction de traitement pour afficher les articles touvés
 */
function tcl_aff_research()
{

    if(isset($_POST['btnSearch']))
    {
        $bd = em_bd_connecter();    // connexion a la base de donnée
        $research = mysqli_real_escape_string($bd, em_html_proteger_sortie(trim($_POST['recherche']))); // on stock le résultat tout en le protégeant 
    }
    else
    {
        $research = ''; // on initialise la variable pour pouvoir l'utiliser 
    }

    echo
        '<main>',
        '<section>',
        '<h2>Recherche des articles</h2>',
        '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>',
        '<form action="recherche.php" method="post">',
        '<table class="recherche">',
        '<tr>',
                '<td>',
                    '<input type="search"  name="recherche" id="txtrecherche" value="', $research, '" placeholder="3 caractères minimum" required minlength="3" size="50">',
                    '<input type="submit" name="btnSearch" value="Rechercher">',
                '</td>',
        '</tr>',
        '</table>',
        '</form>',
        '</section>',
        '</main>';

    if(strlen($research) > 2) // Si la recherche contient au moins 3 caractères, on appelle les fonctions de traitement
    {
        tcl_aff_resumearticle($bd, tcl_traitement_chaine($research));
    }
}

/**
 * Description de la fonction 
 *
 * @param 	String 	$search	Chaine rentrée par l'utilisateur déjà protégée
 * 
 * @return 	String	$res    Partie de la requête SQL prete a être utilisé 
 */
function tcl_traitement_chaine($search)
{
    $save = '';
    $res = '(arResume LIKE \'%'; // chaine résultat initialisé
    $check = false;
    for($i = 0, $iMax = strlen($search); $i < $iMax; $i++)
    {
        if(($search[$i] == ' ') && $check)
        {       // On ajoute une ligne permettant de tester la chaine dans le titre et le resumé
            $res = $res . $save . '%\' OR arTitre LIKE \'%' . $save . '%\') AND (arResume LIKE \'%' ; 
            $save = '';
            $check = false;
        }
        else if($search[$i] != ' ') // si on a pas d'espace, on continue de stocker les lettres jusqu'à ce qu'on tombe sur un espace
        {
            $save = $save . $search[$i];
            $check = true;
        }
    }

    $res = $res . $save . '%\' OR arTitre LIKE \'%' . $save . '%\')' ;  // on rajoute la partie finale de la recherche

    return $res;
}

/**
 * Cette fonction va envoyer une requête sql pour récupérer les résultats s'il y en a
 * Puis les afficher en les triants pas mois de publication
 *
 * @param 	String 	$chaine 	partie de la requête sql traité dans la fonction ci-dessus
 */
function tcl_aff_resumearticle($bd, $chaine)
{
    // On prépare la requête sql avec la chaine préparé en amont
    $sql = "SELECT arResume, arTitre, arDatePublication, arID
            FROM article
            WHERE $chaine
            ORDER BY arDatePublication DESC";

    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    $stock_month = '';  // on initialise la variable qui va stocker le mois en cours

    echo '<main>';

    while($tab = mysqli_fetch_assoc($res))  // on parcoure toute la requête
    {
        $id = $tab['arID'];
        if($stock_month == tcl_get_mois($tab['arDatePublication'])) // si le mois précédent est le même alors on ne fait pas de nouvelle section
        {
            echo '<article class="resume">',
                 '<a href="article.php?id=', $id, '">', 
                 '<img src="', em_url_image_illustration($id), '" alt="Photo d\'illustration | ', $tab['arTitre'], '"></a><h3>',
                 $tab['arTitre'],
                 '</h3>',
                 '<p>', em_html_proteger_sortie($tab['arResume']), '</p>',
                 '<footer>', '<a href="article.php?id=', $id, '">', 'Lire l\'article', '</a>', '</footer>',
                 '</article>';
        }
        else    //ici, on ferme la section précédente si ce n'est pas la première section et on en crée une nouvelle
        {
            if($stock_month != '')
            {
                echo '</section>';
            }
            
            $stock_month = tcl_get_mois($tab['arDatePublication']);
            echo '<section>',
                '<h2>', $stock_month, '</h2>',
                '<article class="resume">',
                '<a href="article.php?id=', $id, '">', 
                '<img src="', em_url_image_illustration($id), '" alt="Photo d\'illustration | ', $tab['arTitre'], '"></a><h3>',
                $tab['arTitre'],
                '</h3>',
                '<p>', em_html_proteger_sortie($tab['arResume']), '</p>',
                '<footer>', '<a href="article.php?id=', $id, '">', 'Lire l\'article', '</a>', '</footer>',
                '</article>';
        }
    }

    echo '</main>';
}

/**
 * Description de la fonction 
 *
 * @param 	int 	$val 	valeur stocké dans la bdd avec le jours, le mois, l'année, l'heure et les minutes
 * 
 * @return 	String 	mois et année contenue dans $val 
 */
function tcl_get_mois($val)
{
    $mois = substr($val, -8, 2);
    $annee = substr($val, 0, -8);

    $month = em_get_tableau_mois(); 

    return mb_strtolower($month[$mois - 1], 'UTF-8') . ' ' . $annee;
}

//_______________________________________________________________
/**
 *  Affichage d'un article sous forme de vignette (image + titre de l'article)
 *  @param  array   $value  tableau associatif issu des enregistrements de la table "article"  
 */
function tcl_aff_vignette($value) {

    $value = em_html_proteger_sortie($value);
    $id = $value['arID'];

    echo    '<a href="./php/article.php?id=', $id, '">', 
                '<img src="', em_url_image_illustration($id, '.'), '" alt="Photo d\'illustration | ', $value['arTitre'], '"><br>',
                $value['arTitre'],
            '</a>';
}

?>