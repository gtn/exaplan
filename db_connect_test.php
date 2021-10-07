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



//https://riptutorial.com/pdo/example/15346/installation-or-setup
//
//https://www.php.net/manual/en/pdo.installation.php
//
//https://www.php-einfach.de/mysql-tutorial/crashkurs-pdo/
//
//
//in /moodle einfach ordner /moodle2 anlegen, und irgendwohin moodledata dafür
//dann hab ich den selben Server, aber mit einer anderen DB
