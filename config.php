<?php 
use jc\session\OriginalSession;
use jc\session\Session;
use jc\db\DB;
use jc\db\PDODriver;

ini_set('display_errors', 1) ;

// 数据库
DB::singleton()->setDriver( new PDODriver("mysql:host=192.168.1.1;dbname=oc",'root','1') ) ;

// 会话
Session::setSingleton( new OriginalSession() ) ;

?>