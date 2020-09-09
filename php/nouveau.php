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

tcl_check_open();

// affichage de l'entête
em_aff_entete('Nouvel article', 'Nouvel article');

echo '<main>',
    '<section>',
    '<h2>Nouvel article</h2>';

// Fonction qui écoute les actions de l'utilisateur
tcl_check_nouveau();

// Fonction qui permet d'afficher la page
tcl_aff_nouveau();


echo '</section>',
    '</main>';

// pied de page
em_aff_pied();

// fin du script
ob_end_flush();

/**
 * Fonction qui vérifie que l'ouverture de la page peut être effectuée.
 * Pour ce faire, elle vérifie que l'utilisateur est connecté et qu'il 
 * possède le droit de rédacteur.
 * Sinon elle redirige vers la page index.php
 */
function tcl_check_open()
{
    if(isset($_SESSION['user']))
    {
        if(!$_SESSION['user']['redacteur'])
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

/**
 * Fonction d'affichage qui écoute les actions de l'utilisateur 
 * Elle réagit donc à l'ajout d'une image, et également l'article 
 * en lui même.
 */
function tcl_check_nouveau()
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
            $bd = em_bd_connecter();
            $arid = tc_get_arid($bd);
            // Nom final du fichier sous la forme : id.jpg
            $target_file = $target_dir . $arid . '.' . pathinfo(basename($_FILES["fileToUpload"]["name"]), PATHINFO_EXTENSION);
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
            if(pathinfo(basename($_FILES["fileToUpload"]["name"]), PATHINFO_EXTENSION) == "jpg")    // controle de l'extension
            {
                if($check !== false) 
                {

                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) // On upload le fichier tout en controlant les erreurs potentiels
                    {
                        echo '<h3>Votre image '. basename( $_FILES["fileToUpload"]["name"]). ' est désormais celle de votre article</h3>';
                        mysqli_free_result($res);
                        mysqli_close($bd);
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

                $bd = em_bd_connecter();

                $arid = tc_get_arid($bd);

                $annee = date(Y);
                $mois = date(m);
                $jour = date(d);
                $heure = date(H);
                $minute = date(i);

                $artitre = mysqli_real_escape_string($bd, strip_tags($_POST['titre_art']));
                $arresume = mysqli_real_escape_string($bd, strip_tags($_POST['resume_art']));
                $artexte = mysqli_real_escape_string($bd, strip_tags($_POST['texte_art']));

                $sql = "INSERT INTO `article` (`arID`, `arTitre`, `arResume`, `arTexte`, `arDatePublication`, `arDateModification`, `arAuteur`) 
                        VALUES ('$arid', '{$artitre}', '{$arresume}', '{$artexte}', '{$annee}{$mois}{$jour}{$heure}{$minute}', NULL, '$pseudo')";
                        
                mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

                mysqli_free_result($res);
                mysqli_close($bd);

                header("Location: article.php?id=$arid");
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
}

/**
 * Fonction qui affiche le corps de la page en vérifiant si certaines informations tel que 
 * le titre, le resumé ou bien le corps de l'article. 
 */
function tcl_aff_nouveau()
{
    echo '<fieldset>',
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
            '<textarea name="titre_art" rows="1" cols="90">';

            if(isset($_POST['titre_art']))
            {
                echo strip_tags($_POST['titre_art']);
            }

    echo    '</textarea><br><br>',
            '<hr><br>',
            '<label> Résumé de l\'article<span class="star">*</span></label>',
            '<textarea name="resume_art" rows="5" cols="110">';

            if(isset($_POST['resume_art']))
            {
                echo strip_tags($_POST['resume_art']);
            }

    echo    '</textarea><br><br>',
            '<hr><br>',
            '<label> Votre article<span class="star">*</span></label>',
            '<textarea name="texte_art" rows="30" cols="110">',
            '[p]  [/p]';

            // affichage des boutons qui permettent de générer les tags en BBcode 

            if(isset($_POST['BtnGras'])){echo strip_tags($_POST['texte_art']),  ' [gras] [/gras] ';}
            else if(isset($_POST['BtnItalique'])){echo strip_tags($_POST['texte_art']),  ' [it] [/it] ';}
            else if(isset($_POST['BtnP'])){echo strip_tags($_POST['texte_art']),  ' [p] [/p] ';}
            else if(isset($_POST['BtnCitation'])){echo strip_tags($_POST['texte_art']),  ' [citation] [/citation] ';}
            else if(isset($_POST['BtnListe'])){echo strip_tags($_POST['texte_art']),  ' [liste] [/liste] ';}
            else if(isset($_POST['BtnItem'])){echo strip_tags($_POST['texte_art']),  ' [item] [/item] ';}
            else if(isset($_POST['BtnLien'])){echo strip_tags($_POST['texte_art']),  ' [a:URL] [/a] ';}
            else if(isset($_POST['Btnretour'])){echo strip_tags($_POST['texte_art']),  ' [br] ';}
            else if(isset($_POST['BtnYtb'])){echo strip_tags($_POST['texte_art']),  ' [youtube:LARGEUR:HAUTEUR:URL] ';}
            else if(isset($_POST['BtnYtbFig'])){echo strip_tags($_POST['texte_art']),  ' [youtube:LARGEUR:HAUTEUR:URL LEGENDE] ';}
            else if(isset($_POST['BtnDec'])){echo strip_tags($_POST['texte_art']),  ' [#NNN] ';}
            else if(isset($_POST['BtnHex'])){echo strip_tags($_POST['texte_art']),  ' [#xNNN] ';}
            else if(isset($_POST['texte_art'])){echo strip_tags($_POST['texte_art']);}
            
    echo    '</textarea><br><br><span class="option">',
            // génération des boutons
            '<input type="submit" name="BtnGras" value="G" class="gras">',
            '<input type="submit" name="BtnItalique" value="I" class="italic">',
            '<input type="submit" name="BtnP" value="Paragraphe">',
            '<input type="submit" name="BtnCitation" value="« »">',
            '<input type="submit" name="BtnListe" value="Liste">',
            '<input type="submit" name="BtnItem" value="Element">',
            '<input type="submit" name="BtnLien" value="Lien" class="lien">',
            '<input type="submit" name="Btnretour" value="↲">',
            '<input type="submit" name="BtnYtb" value="Video">',
            '<input type="submit" name="BtnYtbFig" value="Video + legende">',
            '<input type="submit" name="BtnDec" value="Decimal">',
            '<input type="submit" name="BtnHex" value="hexa">',
            '<br><br></span>',

            '<input type="submit" name="BtnNouveau" value="Publier votre article">',
            '</form>',
            '</fieldset>';
}

?>