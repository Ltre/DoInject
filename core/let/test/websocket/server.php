<?php

ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);
set_time_limit(0);

$host = '127.0.0.1';
$port = '12345';
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($socket, $host, $port) or die('connect faild');
socket_listen($socket);
socket_set_nonblock($socket);

while (true) {
        unset($read); 

     $j = 0; 

     if (count(@$client)) 
     { 
         foreach ($client AS $k => $v) 
         { 
             $read[$j] = $v; 

             $j++; 
         } 
     } 

     $client = $read; 

     if ($newsock = @socket_accept($socket)) 
     { 
         if (is_resource($newsock)) 
         { 
             socket_write($newsock, "$j>", 2).chr(0); 
             
             echo "New client connected $j"; 

             $client[$j] = $newsock; 

             $j++; 
         } 
     } 

     if (count($client)) 
     { 
         foreach ($client AS $k => $v) 
         { 
             if (@socket_recv($v, $string, 1024, MSG_DONTWAIT) === 0) 
             { 
                 unset($client[$k]); 

                 socket_close($v); 
             } 
             else 
             { 
                 if ($string) 
                 { 
                     echo "$k: $string\n"; 
                 } 
             } 
         } 
     } 

     //echo "."; 

     sleep(1);
}

socket_close($socket);
