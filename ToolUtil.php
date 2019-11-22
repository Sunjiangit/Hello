<?php
/**
 * Created by PhpStorm.
 * User: sunjian
 * Date: 2019/8/29
 * Time: 下午3:50
 */

namespace Core\BaseLogger;

abstract class ToolUtil
{

    public static $errCode = 0;

    public static $errMsg = '';

    private static $clientIP = false;

    private static function clearError()
    {
        self::$errCode = 0;
        self::$errMsg = '';
    }

    private static function setERR($code, $msg = '')
    {
        self::$errCode = $code;
        self::$errMsg = $msg;
    }

    public static function checkIP($ip)
    {
        self::clearError();

        $ip = trim($ip);
        $pt = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';

        if (preg_match($pt, $ip) === 1) {
            return true;
        }

        self::$errCode = 10609;
        self::$errMsg = 'IP is invalid.';

        return false;
    }

    public static function getClientIP($recalc = false)
    {
        self::clearError();

        if (!$recalc && self::$clientIP !== false) {
            return self::$clientIP;
        }

        if (isset($_SERVER['HTTP_QVIA'])) {
            $ip = self::qvia2ip($_SERVER['HTTP_QVIA']);

            if ($ip && $ip != '127.0.0.1') {
                return self::$clientIP = $ip;
            }
        }

        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            return self::$clientIP = self::checkIP($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '0.0.0.0';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ',');
            do {
                $tmpIp = explode('.', $ip);
                //-------------------
                // skip private ip ranges
                //-------------------
                // 10.0.0.0 - 10.255.255.255
                // 172.16.0.0 - 172.31.255.255
                // 192.168.0.0 - 192.168.255.255
                // 127.0.0.1, 255.255.255.255, 0.0.0.0
                //-------------------
                if (is_array($tmpIp) && count($tmpIp) == 4) {
                    if (($tmpIp[0] != 10) && ($tmpIp[0] != 172) && ($tmpIp[0] != 192) && ($tmpIp[0] != 127) && ($tmpIp[0] != 255) && ($tmpIp[0] != 0)) {
                        return self::$clientIP = $ip;
                    }
                    if (($tmpIp[0] == 172) && ($tmpIp[1] < 16 || $tmpIp[1] > 31)) {
                        return self::$clientIP = $ip;
                    }
                    if (($tmpIp[0] == 192) && ($tmpIp[1] != 168)) {
                        return self::$clientIP = $ip;
                    }
                    if (($tmpIp[0] == 127) && ($ip != '127.0.0.1')) {
                        return self::$clientIP = $ip;
                    }
                    if ($tmpIp[0] == 255 && ($ip != '255.255.255.255')) {
                        return self::$clientIP = $ip;
                    }
                    if ($tmpIp[0] == 0 && ($ip != '0.0.0.0')) {
                        return self::$clientIP = $ip;
                    }
                }
            } while ($ip = strtok(','));
        }

        if (isset($_SERVER['HTTP_PROXY_USER']) && !empty($_SERVER['HTTP_PROXY_USER'])) {
            return self::$clientIP = self::checkIP($_SERVER['HTTP_PROXY_USER']) ? $_SERVER['HTTP_PROXY_USER'] : '0.0.0.0';
        }

        if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            return self::$clientIP = self::checkIP($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        } else {
            return self::$clientIP = '0.0.0.0';
        }
    }

    public static function getServerIP() {
        if (isset($_SERVER)) {
            if($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}

//End of script
