<?php
require 'rb-postgres.php';

$host = getenv("db_host");
$dbname = getenv("db_name");
$user = getenv("db_user");
$password = getenv("db_password");

R::setup( "pgsql:host=$host;dbname=$dbname", "$user", "$password" );