<?php

session_start();

$_SESSION["carrito"] = [];
session_unset();

session_destroy();

header("Location: index.html");

exit();

?>
