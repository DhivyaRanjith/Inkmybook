<?php
session_start();
require_once '../../includes/functions.php';

session_unset();
session_destroy();

redirect('/inkmybook/index.php');
?>