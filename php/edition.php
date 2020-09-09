<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// Constantes
$TARGET = "../upload/";    // Repertoire cible
$MAX_SIZE = 100000;    // Taille max en octets du fichier
$WIDTH_MAX = 800;    // Largeur max de l'image en pixels
$HEIGHT_MAX = 800;    // Hauteur max de l'image en pixels

// Tableaux de donnees
$tabExt = array('jpg','gif','png','jpeg');    // Extensions autorisees

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();


//Controle de l'id passé dan l'url
if (!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}
if (!em_est_entier($_GET['id']) || $_GET['id'] <= 0) {
    header('Location: ../index.php');
    exit;
}

// vérification possibilité édition
$bd = tcl_check_okay();
$id = $_GET['id'];

// affichage de l'entête
em_aff_entete('Edition article', 'Edition article');

echo '<main>',
    '<section>',
    '<h2>Edition d\'article</h2>';

// Controle de la possibilité de l'édition
tcl_check_edition($bd, $id);

// Affichage de la page
tcl_aff_edition($bd, $id);


echo '</section>',
    '</main>';

// pied de page
em_aff_pied();

// fin du script
ob_end_flush();


/**
 * Fonction qui vérifie que l'ouverture de la page peut être effectuée.
 * Pour ce faire, elle vérifie que l'utilisateur est connecté et qu'il
 * est l'auteur de cet article.
 * Sinon elle redirige vers la page index.php
 */
function tcl_check_okay()
{
    $id = (int)$_GET['id'];
    if(isset($_SESSION['user']))
    {
        $pseudo = $_SESSION['user']['pseudo'];

        $bd = em_bd_connecter();

        if(!tc_check_auteur($pseudo, $id, $bd))
        {
            mysqli_close($bd);
            header('Location: ../index.php');
            exit;
        }
        return $bd;
    }
    else
    {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * Fonction d'affichage qui écoute les actions de l'utilisateur
 * Elle réagit donc à l'ajout d'une image, à l'article
 * en lui même et également avec la suppression de ce dernier
 */
function tcl_check_edition($bd, $id)
{

    if(isset($_POST['submit']) && isset($_FILES["fileToUpload"]))   // on vérifie que l'utilisateur a bien soumis un fichier
    {
        if ($_FILES["fileToUpload"]["size"] > 1000000) // On limite la taille a 1 000 000 octets
        {
            echo '<h3> Votre fichier dépasse la taille max (1.000.000 octets)</h3>';
        }
        else
        {
            $target_dir = "../upload/"; // dossier d'upload
            // Nom final du fichier sous la forme : id.jpg
            $target_file = $target_dir . $id . '.' . pathinfo(basename($_FILES["fileToUpload"]["name"]), PATHINFO_EXTENSION);
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
            if(pathinfo(basename($_FILES["fileToUpload"]["name"]), PATHINFO_EXTENSION) == "jpg")    // controle de l'extension
            {
                if($check !== false)
                {

                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) // On upload le fichier tout en controlant les erreurs potentiels
                    {
                        mysqli_close($bd);
                        header("Location: article.php?id=$id");
                    }
                    else
                    {
                        echo '<h3>Desole, il y a eu un problème avec votre image</h3>';
                    }
                }
                else
                {
                    echo '<h3>Votre fichier n\'est pas une image !</h3>';
                }
            }
            else
            {
                echo '<h3>Seul les images en .jpg sont autorisées !</h3>';
            }
        }
    }

    // Ecoute du bouton de soumission de l'article
    if(isset($_POST['BtnNouveau']))
    {
        if($_POST['titre_art'] != "" && $_POST['resume_art'] != "" && $_POST['texte_art'] != "") // on vérifie qu'il ne manque pas un des éléments
        {

            // On controle que l'utilisateur n'essaye pas d'insérer des caractères non acceptés
            if(strip_tags($_POST['titre_art']) == $_POST['titre_art'] && strip_tags($_POST['resume_art']) == $_POST['resume_art'] && strip_tags($_POST['texte_art']) == $_POST['texte_art'])
            {
                $pseudo = $_SESSION['user']['pseudo'];

                $annee = date(Y);
                $mois = date(m);
                $jour = date(d);
                $heure = date(H);
                $minute = date(i);

                $artitre = mysqli_real_escape_string($bd, strip_tags($_POST['titre_art']));
                $arresume = mysqli_real_escape_string($bd, strip_tags($_POST['resume_art']));
                $artexte = mysqli_real_escape_string($bd, strip_tags($_POST['texte_art']));

                $sql = "UPDATE `article`
                        SET `arTitre` = \"{$artitre}\",
                        `arResume` = \"{$arresume}\",
                        `arTexte` = \"{$artexte}\",
                        `arDateModification` = '{$annee}{$mois}{$jour}{$heure}{$minute}'
                        WHERE `article`.`arID` = $id";

                mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);
                mysqli_close($bd);
                header("Location: article.php?id=$id");
            }
            else
            {
                echo '<br>',
                        '<h3>',
                            'Il est interdit de mettre des balises HTML',
                        '</h3>',
                        '<br>';
            }
        }
        else
        {
            echo '<br>',
                    '<h3>',
                        'Vous devez renseigner tous les champs !',
                    '</h3>',
                    '<br>';
        }
    }

    // Si l'utilisateur a cliqué sur le bouton de suppression
    if(isset($_POST['Sup_sur']))
    {
        // requête qui supprime les commentaires associés à l'article en question
        $sql = "DELETE FROM commentaire WHERE commentaire.coArticle = $id";
        mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

        // requête qui supprime l'article
        $sql = "DELETE FROM article WHERE article.arID = $id";
        mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

        // On supprime l'image associée à l'article
        unlink("../upload/{$id}.jpg");
        mysqli_close($bd);
        // Redirection vers la page d'accueil
        header("Location: ../index.php");
    }

    if(isset($_POST['Sup_non']))
    {
        mysqli_close($bd);
        header("Location: article.php?id=$id");
    }
}

/**
 * Fonction qui affiche le corps de la page en reprenant les informations de
 * l'article à modifier
 */
function tcl_aff_edition($bd, $id)
{
    $sql = "SELECT * FROM article WHERE arID = $id";
    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    if (mysqli_num_rows($res) == 0)
    {
        mysqli_close($bd);
        header('Location: ../index.php');
        exit;
    }

    $tab = mysqli_fetch_assoc($res);

    echo    '<form class="supp" action="#" method="post">';
            if(!isset($_POST['supprimer']))
            {
                echo '<input type="submit" name="supprimer" value="Supprimer votre article">';
            }
            else    // afficher nouveaux boutons pour valider la suppression
            {
                echo '<input type="submit" name="Sup_sur" value="Valider la suppression">',
                     '<br><br>',
                     '<input type="submit" name="Sup_non" value="Annuler la suppression">';
            }
    echo    '</form>',
            '<fieldset>',
            '<legend>',
            'Ecrivez votre article',
            '</legend>',
            '<hr>',
            '<form class="file" action="#" method="post" enctype="multipart/form-data">',
            'Select image to upload:',
            '<input type="file" name="fileToUpload" id="fileToUpload" required>',
            '<input type="submit" value="Ajouter l\'image" name="submit">',
            '</form>',
            '<hr>',
            '<form action="#" method="post" class="nouveau">',
            '<label> Votre titre<span class="star">*</span></label><br>',
            '<textarea name="titre_art" rows="1" cols="90">',
            strip_tags($tab['arTitre']),
            '</textarea><br><br>',
            '<hr><br>',
            '<label> Résumé de l\'article<span class="star">*</span></label>',
            '<textarea name="resume_art" rows="5" cols="110">',
            strip_tags($tab['arResume']),
            '</textarea><br><br>',
            '<hr><br>',
            '<label> Votre article<span class="star">*</span></label>',
            '<textarea name="texte_art" rows="30" cols="110">',
            strip_tags($tab['arTexte']),
            '</textarea>',
            '<br><br>',
            '<input type="submit" name="BtnNouveau" value="Mettre à jour votre article">',
            '</form>',
            '</fieldset>';

}
?>
