<?php
//define koneksi variable
$host = "103.23.103.39:3306";//192.168.1.22
$port = "3306";
$base = "Klaten_IbnuAbbas";//gerbang_uang
$user = "webtagihan";
$pawd = "f@rr3LG4nT3nG";//Bismillah99

// $dbhandle = mysql_connect($host, $user, $pawd) or die("Couldn't connect to SQL Server on $host"); 
  
// //select a database to work with
// $selected = mysql_select_db($base, $dbhandle)
//   or die("Couldn't open database $dbhandle"); 

$dbhandle = new mysqli($host, $user, $pawd, $base, $port);
if ($dbhandle->connect_error) {
    die("Connection failed: " . $dbhandle->connect_error);
}
//select a database to work with
$selected = $dbhandle->select_db($base);
?>
