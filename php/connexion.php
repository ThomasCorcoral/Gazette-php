<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// affichage de l'entête
em_aff_entete('Connexion', 'Connexion');

lpl_aff_formulaire_connexion();

// pied de page
em_aff_pied();

// fin du script
ob_end_flush();

//___________________________________________________________________
/**
 * Fonction qui va afficher la page de connexion
 * Elle fera également appelle à la fonction de gestion
 * des erreurs s'il s'avère que l'utilisateur a précédemment
 * appuyé sur le bouton de connexion
 * 
 * @global $_POST
 */
function lpl_aff_formulaire_connexion(){

  echo '<main>',
          '<section>',
            '<h2>',
                'Formulaire de connexion',
            '</h2>',
            '<p>',
                'Pour vous identifier, remplissez le formulaire ci-dessous.',
            '</p>';

  // On regarde si l'utilisateur a appuyé sur le bouton de connexion précedemment
  if (isset($_POST['btnSeConnecter'])){
    lpl_aff_erreur_connexion();
  }
  else{
      $pseudo = '';
      $mdp = '';
  }

  echo '<form action="connexion.php" method="post">',
        '<table>';

            em_aff_ligne_input('text', 'Pseudo : ', 'pseudo');
            em_aff_ligne_input('password', 'Mot de passe : ', 'mdp');

  echo  '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="btnSeConnecter" value="Se connecter">',
            '<input type="reset" value="Annuler">',
        '</td>',
    '</tr>',
  '</table>',
        '</form>',
            '<p>',
                'Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a>.',
            '</p>',
          '</section>',
      '</main>';
}

//___________________________________________________________________
/**
 * Fonction de traitement de la connexion.
 * Elle connecte l'utilisateur si les identifiants
 * sont corrects et le signal si cela ne correspond pas.
 *
 * @global $_SESSION
 * @global $_POST
 * @return 	int $verif  1 si toutes les verifications sont ok, 0 sinon
 */
function lpl_traitement_connexion() {

    $pseudo = em_html_proteger_sortie(trim($_POST['pseudo']));
    $mdp = em_html_proteger_sortie(trim($_POST['mdp']));
    $verif = 1;

    // vérification du pseudo
    if (empty($pseudo)) {
        $verif = 0;
    }

    // vérification des mots de passe
    if (empty($mdp)) {
        $verif = 0;
    }

    // ouverture de la connexion à la base
    $bd = em_bd_connecter();

    $sql = "SELECT * FROM utilisateur WHERE utPseudo = '{$pseudo}'";
    $res = mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

    if (mysqli_num_rows($res) == 0){
      $verif = 0;
    }
    else {
      $tab = mysqli_fetch_assoc($res);
      if(password_verify($mdp, $tab['utPasse']) == FALSE){
        $verif = 0;
      }
    }

    // Si tout est bien vérifé, on met a jour les variables de SESSION
    if($verif == 1)
    {
      switch($tab['utStatut'])
      {
        case 0 : $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => false, 'administrateur' => false);
              break;
        case 1 : $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => true, 'administrateur' => false);
            break;
        case 2 : $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => false, 'administrateur' => true);
          break;
        case 3 : $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => true, 'administrateur' => true);
          break;
      }

      // Libération de la mémoire associée au résultat de la requête
      mysqli_free_result($res);

      // Fermeture bdd
      mysqli_close($bd);

      // redirection sur la page index.php
      header ('location: ../index.php');
      exit();
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);

    // Fermeture bdd
    mysqli_close($bd);

    return $verif;
}

//___________________________________________________________________
/**
 * Fonction qui affiche l'erreur de connexion s'il
 * y a une erreur relevée par la fonction lpl_traitement_connexion().
 */
function lpl_aff_erreur_connexion()
{
  if(lpl_traitement_connexion() == 0)
  {
    echo '<div class="erreur"><p>Échec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.<p><ul>',
         '</ul></div>';
  }
}

?>
