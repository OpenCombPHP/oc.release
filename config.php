<?php 

// 数据库
DB::singleton()->setDriver( new PDODriver("mysql:host=127.0.0.1;dbname=www",'root','1') ) ;


?>