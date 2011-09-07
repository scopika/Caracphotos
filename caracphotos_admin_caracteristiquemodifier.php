<?php
require_once 'pre.php';
require_once 'auth.php';
include_once realpath(dirname(__FILE__)) . '/../../../fonctions/authplugins.php';
autorisation('caracphotos');

// Si aucun id n'est transmis, ou si la déclinaison n'existe pas, on arrête là le massacre!
require_once realpath(dirname(__FILE__)) . '/../../../classes/Caracteristique.class.php';
if(empty($_REQUEST['id']) || !preg_match('/^[0-9]*$/', $_REQUEST['id'])) return false;
$carac = new Caracteristique();
if(!$carac->charger($_REQUEST['id'])) return false;

// langue
$lang=1;
if(!empty($_GET['lang'])) $lang=$_GET['lang'];

require_once realpath(dirname(__FILE__)) . '/Caracphotos.class.php';
$caracphotos = new Caracphotos();
$caracphotos->renderUI(null, $carac, $lang);
