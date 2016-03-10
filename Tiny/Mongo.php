<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 2015/6/20
 * Time: 18:44
 */
namespace Tiny;

class Mongo
{
    public $type = 'mongodb';
    public static $db; // 当前db对象
    public static $client; // mongo client

    public $key = '_id'; // 主键
    protected $name = 'mongodb'; // 配置数组key
    public $_data; // 你懂的
    protected $table; // 表名
    /* @var $collection \MongoCollection */
    public $collection; // collection

    public function __construct()
    {
        // 设置当前model的表名
        $this->_setTableName();
        // 连接数据库
        $this->_loadDatabase();
    }

    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    private function _loadDatabase()
    {
        if (!isset(self::$db[$this->name])) {
            $config = Config::config()->{$this->name};
            if(!is_array($config))
                throw new \Exception ($config . ' not a valid db config');

            // Load database
            try{

                $config['option'] = isset($config['option']) ? $config['option'] : array();

                if(isset($config['dns']))
                    $dsn = $config['dns'];
                else
                    $dsn = 'mongodb://' . $config['host'] . ':' . $config['port'];

                if(!isset(self::$client[$dsn])){
                    self::$client[$dsn]= new \MongoClient($dsn, $config['option']);
                }

            }catch (\Exception $e){
                throw new \Exception ('connection mongodb failed.' . json_encode($config));
            }

            if(!isset($config['db'])){
                throw new \Exception ('empty mongodb db set');
            }
            $db = self::$client[$dsn]->$config['db'];

            $this->collection = $db->{$this->table};
            self::$db[$this->name] = $db;
        }

        $this->collection = self::$db[$this->name]->{$this->table};

        return $this->getDb();
    }

    public function getDb()
    {
        return self::$db[$this->name];
    }

    private function _setTableName()
    {
        if (empty($this->table)) {
            $name = get_class($this);
            if ($pos = strrpos($name, '\\')) { //有命名空间
                $this->table = $this->_parseName(substr($name, $pos + 1));
            } else {
                $this->table = $this->_parseName($name);
            }
        }
        return $this->table;
    }

    public function getTableName()
    {
        return $this->table;
    }

    private function _parseName($name, $type = 0)
    {
        return Helper::parseName($name, $type);
    }

    // 获取主键
    public function getKey()
    {
        return $this->key;
    }

    public function find(array $condition = array(), array $field = array())
    {
        $cursor = $this->collection->find($condition, $field);
        $rows = array();
        while($cursor->hasNext()){
            $rows[] = $cursor->getNext();
        }

        return $rows;
    }

    public function findOne($condition = array(), array $field = array())
    {
        if(is_string($condition)){
            return $this->collection->findOne(array($this->key => new \MongoId($condition)), $field);
        }elseif(is_array($condition)){
            return $this->collection->findOne($condition, $field);
        }else{
            throw new \Exception ('condition must be string or array');
        }
    }

    public function delete($criteria, array $option = array())
    {
        if(is_array($criteria)){
            return $this->collection->remove($criteria, $option);
        }elseif(is_string($criteria)){
            return $this->collection->remove(array($this->key => new \MongoId($criteria)), $option);
        }else{
            throw new \Exception ('delete criteria type error');
        }
    }

    public function save($data = array(), array $option = array())
    {
        if($data){
            return $this->collection->save($data, $option);
        }elseif($this->_data){
            return $this->collection->save($this->_data, $option);
        }else{
            throw new \Exception ('save data not be empty');
        }
    }

    public function update(array $criteria = array(), array $new = array(), array $option = array())
    {
        if($criteria && $new){
            return $this->collection->update($criteria, $new, $option);
        }elseif($this->_data && isset($this->_data[$this->key])){
            $data = $this->_data;
            unset($data[$this->key]);
            if(is_string($this->_data[$this->key])) $this->_data[$this->key] = new \MongoId($this->_data[$this->key]);
            return $this->collection->update(array($this->key => $this->_data[$this->key]), array('$set' => $data));
        }else{
            throw new \Exception ('update data not be empty');
        }
    }

    public static function attributes(){
        return array();
    }
}