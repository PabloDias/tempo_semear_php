<?php
// src/admin/logout_admin.php

session_start();

// Remove todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login do ADMIN
header('Location: /login.php');
exit();