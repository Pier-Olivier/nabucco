<?php

$baze = new \PDO('mysql:host=localhost; dbname=test', 'root', '');
$baze->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);

$baze->exec("set names utf8");
?>