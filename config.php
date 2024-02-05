<?php

// Monitor configuration
return array(
      // enable/disable script work
     'enabled' => true,

     // Default scan path is `script root`
	 // 'root'   => '/root/to/scan',

     // allowed multi root
     'root' => [
		'/srv/www/site1/public',
		'/srv/www/site2/public',
		'/etc/apache2',
		],

     //'root' => array(
     //   __DIR__ . '\\1',
     //   __DIR__ . '\\2'
     //),

     // skip this dirs
     'ignore_dirs' => [
		'/srv/www/site1/public/trash',
		'/srv/www/site2/public/cache',

     ],

     // ServerTag for text reports, default _SERVER[SERVER_NAME]
	 'server' => 'SERVERNAME',

     // files pattern
	 'files' => '(\.php.?|\.htaccess|\.json|\.js|\.yaml|\.inc|\.css|\.conf)$',

      // write logs to ./logs/Ym/d-m-y.log
     'log' => true,

      // notify administrator email
	 'mail' => array(
	 	'from'   => 'root@fsmon',
	 	'to'   	 => 'TO-MAIL@MAIL.COM',

	 	// disabled by default
//	 	'enable' => true,
	 	'enable' => false,
	 ),
	 'telegram' => array (
// set telegram bot token
		'TELEGRAM_TOKEN' => '',
// set telegram ID (personal or group/supergroup)
		'TELEGRAM_CHATID' => '',
//	 	'enable' => false,
	 	'enable' => true,
//	 	'filesend' => false,
	 	'filesend' => true,
//              message - see at line 182
//              file caption - see at line 187
	 )

);
?>