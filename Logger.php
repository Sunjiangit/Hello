<?php
namespace Core\BaseLogger;

if (!defined('LOG_ROOT')) {
    define('LOG_ROOT', '/usr/share/nginx/html/saas/log/2019/');
};

class Logger
{
    const DEBUG = 0;
    const INFO  = 1;
    const WARN  = 2;
    const ERR   = 3;
    const FATAL = 4;

    const BASE_DIRECTORY = '/usr/share/nginx/html/';

    private $level;

    private $logDate;

    private $logFile;

    private $logFileName;

    private $ip;

    private static $log;

    private $records = array();

    private $maxRecordCount = 1;

    private $curRecordCount = 0;

    private $processID = '0';

    private static $clientIP = false;

    function __construct($logname = '')
    {
        if ( !empty(self::$log) ) {
            return;
        }

        if ( strlen($logname) ) {
            $logname		= self::_transFilename($logname);
            $logname		= basename($logname, '.log');
        } else {
            $logname		= basename($_SERVER['SCRIPT_NAME'], '.php');
        }

        global $_LOGLEVEL;

        $this->logFileName	= $logname . '.log';
        $this->level       = $_LOGLEVEL >= 0 ? $_LOGLEVEL : self::INFO;
        $this->ip			= ToolUtil::getClientIP();
        $this->processID	= str_pad( (function_exists('posix_getpid') ? posix_getpid() : 0), 5 );

        self::$log			= $this;
    }

    function __destruct()
    {
        if ( $this->curRecordCount > 0 ) {
            if ( empty($this->logFile) || $this->logDate != date('Ymd') ) {
                if ( !empty($this->logFile) ) {
                    fclose($this->logFile);
                }
                $this->_setHandle();
            }

            $str = implode("\n", $this->records);
            fwrite($this->logFile, $str . "\n");
            $this->records = array();
            $this->curRecordCount = 0;
        }

        if ( !empty($this->logFile) ) {
            fclose($this->logFile);
        }
    }

    private function _setHandle()
    {
        $this->logDate	= date('Ymd');
        $logDir 		= LOG_ROOT . $this->logDate . '/';

        if ( !file_exists($logDir) ) {
            @umask(0);
            @mkdir($logDir, 0777, true);
        }

        $this->logFile	= fopen($logDir . $this->logFileName, 'a');
    }

    private function _transFilename($filename)
    {
        if  ( !strlen($filename) ) {
            return $filename;
        }

        $filename = str_replace('\\', '#', $filename);
        $filename = str_replace('/', '#', $filename);
        $filename = str_replace(':', ';', $filename);
        $filename = str_replace('"', '$', $filename);
        $filename = str_replace('*', '@', $filename);
        $filename = str_replace('?', '!', $filename);
        $filename = str_replace('>', ')', $filename);
        $filename = str_replace('<', '(', $filename);
        $filename = str_replace('|', ']', $filename);

        return $filename;
    }

    public static function init( $logname = "")
    {
        if ( empty(self::$log) ) {
            if ( empty($logname) ) {
                $stack = debug_backtrace();
                $top_call = $stack[0];
                $logname = basename($top_call['file'], '.php');
            }

            self::$log = new Logger($logname);
        }
    }

    public static function changelog( $logname )
    {
        if ( self::$log ) {

            self::$log->logFileName	= $logname . '.log';
            if ( !empty(self::$log->logFile) ) {
                fclose(self::$log->logFile);
            }
            self::$log->logFile = false;

        } else {

            self::init( $logname);
        }
    }

    public static function _write($s)
    {
        if ( !strlen($s) ) {
            return false;
        }

        self::$log->records[] = $s;
        self::$log->curRecordCount++;

        if ( self::$log->curRecordCount >= self::$log->maxRecordCount ) {
            if ( empty(self::$log->logFile) || self::$log->logDate != date('Ymd') ) {
                if ( !empty(self::$log->logFile) ) {
                    fclose(self::$log->logFile);
                }
                self::$log->_setHandle();
            }
            $str = implode("\n", self::$log->records);
            fwrite(self::$log->logFile, $str . "\n");
            self::$log->curRecordCount = 0;
            self::$log->records = array();
        }

        return true;
    }

    public static function debug($str) {
        if (is_object($str) || is_array($str)) {
            $str = var_export($str, true);
        }

        if ( !strlen($str) ) {
            return false;
        }

        if ( empty(self::$log) ) {
            self::$log = new Logger();
        }

        if (  self::DEBUG < self::$log->level) {
            return false;
        }

        $trc = debug_backtrace();
        //$s = date('Y-m-d H:i:s');
        $s = self::getmillitime();
        $s .= "\tDEBUG\tPID:" . self::$log->processID;
        $s .= "\t" . substr($trc[0]['file'],strlen(self::BASE_DIRECTORY));
        $s .= "\tline " . $trc[0]['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }

    public static function info($str) {
        if (is_object($str) || is_array($str)) {
            $str = var_export($str, true);
        }

        if ( !strlen($str) ) {
            return false;
        }

        if ( empty(self::$log) ) {
            self::$log = new Logger();
        }

        if (  self::INFO < self::$log->level) {
            return false;
        }

        $trc = debug_backtrace();
        //$s = date('Y-m-d H:i:s');
        $s = self::getmillitime();
        $s .= "\tINFO\tPID:" . self::$log->processID;
        $s .= "\t" . substr($trc[0]['file'],strlen(self::BASE_DIRECTORY));
        $s .= "\tline " . $trc[0]['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }

    public static function notice($str)
    {
        if ( !strlen($str) ) {
            return false;
        }

        if ( empty(self::$log) ) {
            self::$log = new Logger();
        }

        if ( self::INFO < self::$log->level ) {
            return false;
        }

        $trc = debug_backtrace();
        //$s = date('Y-m-d H:i:s');
        $s = self::getmillitime();
        $s .= "\tNOTICE\tPID:" . self::$log->processID;
        $s .= "\t" . substr($trc[0]['file'],strlen(self::BASE_DIRECTORY));
        $s .= "\tline " . $trc[0]['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }

    public static function warn($str)
    {
        if ( !strlen($str) ) {
            return false;
        }

        if ( empty(self::$log) ) {
            self::$log = new Logger();
        }

        if ( self::WARN < self::$log->level ) {
            return false;
        }

        $trc = debug_backtrace();
        //$s = date('Y-m-d H:i:s');
        $s = self::getmillitime();
        $s .= "\tWARN\tPID:" . self::$log->processID;
        $s .= "\t" . substr($trc[0]['file'],strlen(self::BASE_DIRECTORY));
        $s .= "\tline " . $trc[0]['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }

    public static function err($str)
    {
        if ( !strlen($str) ) {
            return false;
        }

        if ( empty(self::$log) ) {
            self::$log = new Logger();
        }

        if ( self::ERR < self::$log->level) {
            return false;
        }

        $trc = debug_backtrace();
        //$s = date('Y-m-d H:i:s');
        $s = self::getmillitime();
        $s .= "\tERR\tPID:" . self::$log->processID;
        $s .= "\t" . substr($trc[0]['file'],strlen(self::BASE_DIRECTORY));
        $s .= "\tline " . $trc[0]['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }

    private static function getmillitime()
    {
        list($usec, $sec) = explode(" ", microtime());
        $msec = intval($usec*1000);
        $sec = date('Y-m-d H:i:s',$sec);
        return  $sec . '.' . sprintf( "%03d",$msec);
    }
}

//End of script
