#!/usr/bin/php
<?php

/**
 * File System monitor | FSMon
 * @version 1.0.4.20240205.00
 * @author j4ck <rustyj4ck@gmail.com>
 * @modified DoC <ruslan_smirnoff@mail.ru>
 * @link https://github.com/rustyJ4ck/FSMon
 */

$GLOBALS['debbug']="false";
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors','on');
$root_dir = $this_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$config = include($this_dir . 'config.php');	// read config
if (!$config['enabled']) {exit;}		// exit if disable

// find substring in array
function in_arrayt($path, $dirs = array()) {
    foreach($dirs as $a) {
        if (stripos($path,$a) !== false) return true;
    }
    return false;
}

// send message to telegram
function message_to_telegram($text) {
global $config;
    $ch = curl_init();
    curl_setopt_array(
        $ch,
        array(
            CURLOPT_URL => "https://api.telegram.org/bot" . $config['telegram']['TELEGRAM_TOKEN'] . "/sendMessage",
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => array(
                "parse_mode"=> "HTML",
                "chat_id" => $config['telegram']['TELEGRAM_CHATID'],
                "text" => $text,
            ),
        )
    );
    curl_exec($ch);
    curl_close($ch);
    sleep(2);
}

// send file to telegram
function file_to_telegram($tgfile,$caption) {
global $config;
    if ($tgfile != "") {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $config['telegram']['TELEGRAM_TOKEN'] . "/sendDocument?chat_id=" . $config['telegram']['TELEGRAM_CHATID']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	$cFile = new CURLFile($tgfile);
	curl_setopt($ch, CURLOPT_POSTFIELDS, [
	    "document" => $cFile,
	    "caption" => $caption,
	]);
	$result = curl_exec($ch);
	curl_close($ch);
	sleep(2);
    }
}

if (isset($config['root'])) {
    $root_dir = $config['root'];
}

$files_preg = @$config['files'];

// server name
$SERVER_NAME = @$config['server'] ? $config['server'] : @$_SERVER['SERVER_NAME'];
$SERVER_NAME = $SERVER_NAME ? $SERVER_NAME : 'localhost';

$precache = $cache = array();
if ($GLOBALS['debbug'] == "true") {console::start();}
$first_run = false;

// read cache
$cache_file = $this_dir . '.cache';
if (file_exists($cache_file)) {
    $precache = $cache = unserialize(file_get_contents($cache_file));
} else {
    $first_run = true;
}

// scan
$result = array();
$checked_ids = array();
$tree = fsTree::tree($root_dir, $config['ignore_dirs'], $files_preg);
if ($GLOBALS['debbug'] == "true") {console::log("[1] list files");}
foreach ($tree->getFilesIterator() as $f) {
    if ($GLOBALS['debbug'] == "true") {console::log("...%s", $f);}
    $id = fsTree::fileId($f);
    $checked_ids [] = $id;
    $csumm = fsTree::crcFile($f);
    if (isset($cache[$id])) {
        // existed
        if ($cache[$id]['crc'] != $csumm) {
            // modded
            $cache[$id]['crc']  = $csumm;
            $cache[$id]['file'] = $f;
            $result[]           = array('file' => $f, 'result' => 'modified');
        } else {
            // old one
        }
    } else {
        // new one
        $cache[$id]['crc']  = $csumm;
        $cache[$id]['file'] = $f;
        $result[]           = array('file' => $f, 'result' => 'new');
    }

}

unset($tree);
if ($GLOBALS['debbug'] == "true") {console::log("[2] check for deleted files");}
$arrk = array_keys($precache);
$my_arrd = my_array_diff($arrk, $checked_ids);
$deleted = (!empty($precache) ? $my_arrd : false);

if (!empty($deleted)) {
    foreach ($deleted as $id) {
        $result[] = array('file' => $precache[$id]['file'], 'result' => 'deleted');
        unset($cache[$id]);
    }
}

if ($GLOBALS['debbug'] == "true") {console::log("[3] result checks");}

if (!empty($result)) {
    $buffer = '*** BEGIN '.date("Y-m-d_H-i-s").PHP_EOL;
    if ($GLOBALS['debbug'] == "true") {console::log('Reporting...');}

    foreach ($result as $r) {
        $line = sprintf("[%10s]\t%s\t%s kb\t%s"
            , $r['result']
            , $r['file']
            , @round(filesize($r['file']) / 1024, 1)
            , @date('d.m.Y H:i', filemtime($r['file']))
        );
        if ($GLOBALS['debbug'] == "true") {console::log($line);}
        $buffer .= $line;
        $buffer .= PHP_EOL;
    }

    if ($first_run) {
        $buffer = "[First Run]\n\n" . $buffer;
    }

    // log 
    $fname="";
    if (@$config['log']) {
        $logs_dir = dirname(__FILE__) . '/logs/' . date('Ym');
        @mkdir($logs_dir, 0770, 1);
	$buffer .= PHP_EOL.'*** END '.date("Y-m-d_H-i-s").PHP_EOL;
	$fname=$logs_dir . '/' . date('d-H-i') . '.log';// full log file path
	$sfname=date('d-H-i') . '.log';			// log file name
        file_put_contents($fname, $buffer);
    }

    // send mail
    if (@$config['mail']['enable'] && !$first_run) {
        $from = @$config['mail']['from'] ? $config['mail']['from'] : 'root@localhost';
        $to   = @$config['mail']['to'] ? $config['mail']['to'] : 'root@localhost';

        if ($to === 'root@localhost') {
            if ($GLOBALS['debbug'] == "true") {console::log("Empty mail@to");}
        } else {
            $subject = "FSMon report for " . $SERVER_NAME;
            $buffer .= "\n\nGenerated by FSMon | " . date('Y-m-d_H-i-s') . '.';
            if ($GLOBALS['debbug'] == "true") {console::log('Message to %s', $to);}
            mailer::send($from, $to, $subject, $buffer);
        }
    }

    // send message to telegram
    if (@$config['telegram']['enable'] && !$first_run) {
            message_to_telegram("&#127384; Server: <strong>" . $SERVER_NAME . "</strong> - <b><i>Code changes detect at " . date("Y-m-d H:i") . "!</i></b>");
    }

    // send file to telegram
    if (@$config['telegram']['filesend'] && !$first_run) {
            file_to_telegram($fname,"Code change at " . date("Y-m-d H:i") . "! Server: " . $SERVER_NAME . ", log file: " . $sfname);
    }

} else {
    if ($GLOBALS['debbug'] == "true") {console::log('All clear');}
}

// save result
file_put_contents($cache_file , serialize($cache));

if ($GLOBALS['debbug'] == "true") {console::log('Done');
    console::log('Memory [All/Curr] %.2f %.2f', memory_get_peak_usage(), memory_get_usage());
}

// Done

function my_array_diff(&$a, &$b) {
    $map = $out = array();
    foreach($a as $val) $map[$val] = 1;
    foreach($b as $val) if(isset($map[$val])) $map[$val] = 0;
    foreach($map as $val => $ok) if($ok) $out[] = $val;
    return $out;
}

class console {
    private static $time;
    static function start() {
        self::$time = microtime(1);
    }
    static function log() {
        $args   = func_get_args();
        $format = array_shift($args);
        $format = '%.5f| ' . $format;
        array_unshift($args, self::time());
        echo vsprintf($format, $args);
        echo PHP_EOL;
    }
    private static function time() {
        return microtime(1) - self::$time;
    }
}

// Mail helper
class mailer {
    static function send($from, $to, $subject, $message) {
        $headers = 'From: ' . $from . "\r\n" .
            'Reply-To: ' . $from . "\r\n" .
            "Content-Type: text/plain; charset=\"utf-8\"\r\n" .
            'X-Mailer: PHP/fsmon';
        return mail($to, $subject, $message, $headers);
    }
}

// FileSystem helpers
class fsTree {
    const DS = DIRECTORY_SEPARATOR;
    const IGNORE_DOT_DIRS = true;

    // Find files
    static function lsFiles($o_dir, $files_preg = '') {
        $ret = array();
        $dir = @opendir($o_dir);
        if (!$dir) {
            return false;
        }
        while (false !== ($file = readdir($dir))) {
            $path = $o_dir . /*DIRECTORY_SEPARATOR .*/
                $file;
            if ($file !== '..' && $file !== '.' && !is_dir($path)
                && (empty($files_preg) || (!empty($files_preg) && preg_match("#{$files_preg}#", $file)))
            ) {
                $ret []= $path;
            }
        }
        closedir($dir);
        return $ret;
    }

    // Scan dirs. One level
    static function lsDirs($o_dir) {
        $ret = array();
        $dir = @opendir($o_dir);
        if (!$dir) {
            return false;
        }
        while (false !== ($file = readdir($dir))) {
            $path = $o_dir /*. DIRECTORY_SEPARATOR*/ . $file;
            if ($file !== '..' && $file !== '.' && is_dir($path)) {
                $ret [] = $path;
            }
        }
        closedir($dir);
        return $ret;
    }

    private $_files = array();
    private $_dirs  = array();

    function getFilesIterator() {
        return new ArrayIterator($this->_files);
    }

    function getDirsIterator() {
        return new ArrayIterator($this->_dirs);
    }

    /**
     * Build tree
     *
     * @desc build tree
     * @param string|array root
     * @param array &buffer
     * @param array dir filters
     * @param string file regex filter
     * @return fsTree
     */

    public static function tree($root_path, $dirs_filter = array(), $files_preg = '.*')
    {
        $self = new self;
        $self->buildTree($root_path, $dirs_filter, $files_preg);
        return $self;
    }

    public function buildTree($root_path, $dirs_filter = array(), $files_preg = '.*')
    {
        if (empty($root_path)) {
            return;
        }

        if (!is_array($root_path)) {
            $root_path = array($root_path);
        }

        foreach ($root_path as $path) {

            $_path = $path; //no-slash

            if (substr($path, -1, 1) != self::DS) $path .= self::DS;

            if ($GLOBALS['debbug'] == "true") {console::log("ls %s ", $_path);}
            $skipper = false;

            if (self::IGNORE_DOT_DIRS) {
                $exPath = explode(self::DS, $_path);
                $dirname = array_pop($exPath);
                $skipper = (substr($dirname, 0, 1) === '.');
            }

            if (!$skipper && (empty($dirs_filter) || !in_arrayt($_path, $dirs_filter))) {
                $dirs = self::lsDirs($path);
                if ($dirs === false) {
                    //opendir(/var/www/html/...): failed to open dir: Permission denied
                    if ($GLOBALS['debbug'] == "true") {console::log('..opendir failed!');}
                } else {
                    $files = self::lsFiles($path, $files_preg);
                    $this->_dirs []= $path;
                    $this->_files = array_merge($this->_files, $files);
                    $this->buildTree($dirs, $dirs_filter, $files_preg);
                }

            } else {
                if ($GLOBALS['debbug'] == "true") {console::log("...skipped %s", $_path);}
            }
        }
    }

    // unique file name
    public static function fileId($path) {
        return md5($path);
    }

    // Checksum
    public static function crcFile($path) {
        return sprintf("%u", crc32(file_get_contents($path)));
    }
}
?>