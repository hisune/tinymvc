<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-31
 * Time: 下午3:40
 */
namespace Tiny;

class Database
{
    public $pdo = array();

    public $type = NULL;

    protected $config = array();

    public static $queries = array();

    public static $lastQuery = NULL;

    /**
     * Set the database type and save the config for later.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        // Auto-detect database type from DNS
        $this->type = current(explode(':', $config['dns'], 2));

        $this->config = $config;
    }

    public function connect($type = 'master')
    {
        extract($this->config);

        if (isset($separate) and $separate) {
            $dns = explode(',', $dns);
            if ($type == 'master')
                $dns = $dns['0'];
            else {
                if (isset($rand_read) and $rand_read)
                    $dns = $dns[mt_rand(0, count($dns) - 1)];
                elseif (isset($dns['1']))
                    $dns = $dns['1'];
                else
                    $dns = $dns['0'];
            }
        }

        // Connect to PDO
        !isset($params) && $params = array();
        $this->pdo[$type] = new \PDO($dns, $username, $password, $params);
        // PDO should throw exceptions
        $this->pdo[$type]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * slave use query
     */
    public function query($sql, array $params = NULL)
    {
        return $this->_runPdo($sql, $params, 'slave');
    }

    /**
     * master use execute
     */
    public function execute($sql, array $params = NULL)
    {
        return $this->_runPdo($sql, $params, 'master');
    }

    private function _runPdo($sql, $params, $type)
    {
        $time = microtime(TRUE);

        self::$lastQuery = $sql;

        // Connect if needed
        if (!isset($this->pdo[$type])) $this->connect($type);

        $statement = $this->pdo[$type]->prepare($sql);

        try{
            $statement->execute($params);
        }catch (\Exception $e){
            \Tiny\Exception::exception($e, 'SQL: ' . self::$lastQuery);
        }

        // Save query results by database type if allowed
//        if(isset($this->config['log_queries']) && $this->config['log_queries'])
            self::$queries[$this->type][] = array(microtime(TRUE) - $time, $sql, $params);

        return $statement;
    }
}