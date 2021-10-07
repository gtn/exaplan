<?php
// TODO: configdatei

$pdo = new PDO('mysql:host=localhost;dbname=moodle', 'root', '');

$statement = $pdo->prepare("SELECT * FROM mdl_block_exacompexamples WHERE title = ?");
$statement->execute(array('Makros (LT)'));
$a = $statement->fetch();

echo "done";