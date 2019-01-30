<?php
require 'iBot.class.php';

$server = 'chat.freenode.net';  //server
$port = 6667;                   //port
$nick = 'iBot9';                //nick
$ident = 'iBotByBusterz';       //ident
$gecos = 'iBot 1.0';            //gecos
$prefix = '@';                  //prefix for commands
$maxtime = 600;                 //maximum flooding time in seconds

$iBot = new iBot($server, $port, $nick, $ident, $gecos, $prefix, $maxtime);
$iBot->run();
