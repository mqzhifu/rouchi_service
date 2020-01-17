<?php

namespace Jy\Db\Db;

/**
 * PDODriver
 */
class PDODriver {

    private $_instance;

    private function __construct() {

	}

    /**
     * getInstance
     *
     * @param mixed $configs
     * @static
     * @access public
     * @return void
     */
    public static function getInstance($configs) {
        try {
            if(empty($configs['host'])) {
                throw new \Exception('param host is empty!', 1);
            }

            if(empty($configs['user'])) {
                throw new \Exception('param user is empty!', 2);
            }

            if(empty($configs['dbname'])) {
                throw new \Exception('param dbname is empty!', 3);
            }
            if(empty($configs['port'])) {
                throw new \Exception('param prot is empty!', 5);
            }
            $dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $configs['dbname'], $configs['host'], $configs['port']);

            try {
                $options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8');
                if (PHP_SAPI == 'cli') {
                    $db = new \PDO($dsn, $configs['user'], $configs['passwd'], $options);
                } else {
                    $db = new \PDO($dsn, $configs['user'], $configs['passwd'], $options);
                }
            } catch (\PDOException $e) {

                usleep(10000);
                try {
                    if (PHP_SAPI == 'cli') {
                        $db = @new \PDO($dsn, $configs['user'], $configs['passwd'], $options);
                    } else {
                        $db = new \PDO($dsn, $configs['user'], $configs['passwd'], $options);
                    }
                } catch (\PDOException $re) {
                    throw new \Exception('Connection failed! ' . $e->getMessage() . '|' . $re->getMessage(), $e->getCode(), $e);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        $timeout = isset($configs['timeout']) ? $configs['timeout'] : 1;
        $db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_ASSOC);
        $db->setAttribute(\PDO::ATTR_TIMEOUT, $timeout);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $db;
    }


}
