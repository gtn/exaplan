<?php
// TODO: configdatei
//$pdo = new PDO('mysql:host=localhost;dbname=moodle', 'root', '');
//
//$statement = $pdo->prepare("SELECT * FROM mdl_block_exacompexamples WHERE title = ?");
//$statement->execute(array('Makros (LT)'));
//$a = $statement->fetch();
//
//echo "done";


$pdo = new PDO('mysql:host=localhost;dbname=moodle2', 'root', '');

$statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmodulesets");
$statement->execute(array());
$modulesets = $statement->fetchAll();
foreach ($modulesets as $moduleset){
    echo $moduleset["title"].'<br>';
}

echo "done";