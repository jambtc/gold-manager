<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>

<?php
include "config.php";

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
if (!$link) {
    die('Could not connect: ' . mysqli_error());
}

$rest = mysqli_select_db($link, DB_DATABASE);
if (!$rest) {
    die('Could not selecte dbase: ' . mysqli_error());
}

?>



<body>
</body>
</html>
