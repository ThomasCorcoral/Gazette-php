<?php

// NB : il manque dans ce script la protection des sorties avec htmlentities()
echo '<pre>';
var_dump($_POST);
echo '</pre>';

echo '<hr><hr><pre>', print_r($_POST, true), '</pre>';

echo '<hr><hr>';

foreach($_POST as $cle => $value){
    echo $cle, ':', $value, '<br>';
}

?>
