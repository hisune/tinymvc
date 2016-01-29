<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-31
 * Time: 下午3:40
 *
 * TODO 兼容mysql以外的其他数据库，暂时只需要处理`符号即可
 *
 * 公用config和db对象，如果修改了某一个model的db load，将会对后面的其他model产生影响
 *
 * 0. 仿tp的orm，更简单，效率更优。注意使用时一定要绑定变量！
 * 1. 支持主从读写分离，支持主从随机读取；
 * 2. 对于直接执行原生sql语句，主库用execute，从库用query，原生sql也支持变量绑定
 * 3. 支持prefix
 * 4. 支持变量绑定的函数有：where，第一个参数为string，第二个参数为绑定变量数组
 * 5. 不支持变量绑定的函数有：field,table,join,having,order,group,limit，只支持一个string参数(limit除外)。limit会对传入的值强行intval，防止后端分页没有处理用户输入的安全隐患
 * 6. 对于链式操作，会在table和join中自动加入prefix，指定表的情况：__TABLE_NAME__会专成：pre_table_name
 * 7. 对于query和execute不支持自动加入prefix，由于本身就是原生sql语句，直接写表名即可
 *
 * select使用方法(find)：
 * $orders = new \Model\Orders;
 * $orders
 *     ->alias('o') // 或者用 ->table('__ORDERS__ o')
 *     ->field('o.order, o.id'))
 *     ->order('? desc', array('o.id'))
 *     ->where('o.id = ? or o.order = "?"',array("5' and 1=2 union select * from user where id=1/*")) // 注入测试
 *     ->limit('1/*,1') // limit强制整型测试
 *     ->join('__TEST__ t on t.id = o.test_id', 'left')
 *     ->group('?', array('o.order'))
 *     ->having('count(*) > ?', array('1'))
 *     ->find() // find()无参数
 * 其他CURD方法：
 * findOne([int $id]) // R，指定id为where条件
 * save([array $data], [boolean $replace]) // C
 * update([array $data], [boolean $all]) // U，慎用all
 * delete([int $id], [boolean $all]) // D，慎用all
 * increment(string $column, [int $value]);
 * query(string $sql, [array $param], [boolean $fetchAll])
 * execute(string $sql, [array $param])
 * 单列数据统计方法，多列或其他复杂情况用field：
 * count([string $column])
 * max([string $column])
 * avg([string $column])
 * min([string $column])
 * sum([string $column])
 * distinct([string $column])
 * 其他说明：
 * field第一参数支持array，第一参数为array时，不支持别名，别名用string
 * where第一参数支持array，此时第二参数无效；第一参数为array时，不支持别名，别名请用string；只支持绑定条件变量。
 * limit支持string类似，limit('0, 10')，或双参数类似，limit(0, 10)
 */

namespace Tiny;

/**
 * @method ORM field(string $field, array $param = array())
 * @method ORM table(string $table)
 * @method ORM alias(string $alias)
 * @method ORM where(string $where, array $param = array())
 * @method ORM having(string $having)
 * @method ORM group(string $group)
 * @method ORM order(string $order)
 * @method ORM limit(mixed $skip, int $limit = null)
 */
class ORM
{
    public $type = 'mysql';
    public static $db; // db对象
    public static $prefix; // 表前缀

    public $key = 'id'; // 主键
    protected $name = 'database'; // 配置数组key
    public $_data; // 你懂的
    protected $table; // 表名
    protected $options; // 操作对象
    protected $prepareParam = array(); // 预处理数组

    private $methods = array('field', 'table', 'alias', 'where', 'having', 'group', 'order', 'limit'); // 链式操作方法
    private $multiMethods = array('join'); // 支持多次执行的链式操作方法
    private $count = array('count', 'max', 'avg', 'min', 'sum', 'distinct'); // 单字段的统计方法
    private $selectSql = 'SELECT %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';

    protected $updatedAt = true; // 打开确保数据库里有这个字段，且为timestamp类型
    protected $createdAt = true; // 打开确保数据库里有这个字段，且为timestamp类型
    protected $timeType = 'timestamp';

    public function __construct()
    {
        // 连接数据库，lazy load
        $this->_loadDatabase();
        // 设置当前model的表名
        $this->_setTableName();
    }

    public function __call($method, $args)
    {
        if (in_array($method, $this->methods)){
            $this->options[$method] = $args;
            return $this;
        }elseif(in_array($method, $this->multiMethods)){
            $this->options[$method][] = $args;
            return $this;
        }elseif(in_array($method, $this->count))
            return $this->_count($method, isset($args['0']) ? $args['0'] : null);
    }

    public function __set($key, $value)
    {
        $this->_data[$this->_parseName($key)] = $value;
    }

    private function _setTableName()
    {
        if (empty($this->table)) {
            $name = get_class($this);
            if ($pos = strrpos($name, '\\')) { //有命名空间
                $this->table = self::$prefix . $this->_parseName(substr($name, $pos + 1));
            } else {
                $this->table = self::$prefix . $this->_parseName($name);
            }
        }
        return $this->table;
    }

    public function getTableName()
    {
        return isset($this->options['table']['0']) ? '`' . $this->options['table']['0'] . '`' : '`' . $this->table . '`';
    }

    /**
     * Load database connection
     */
    private function _loadDatabase()
    {
        if (!isset(self::$db[$this->name])) {
            $config = Config::config()->{$this->name};
            if(!is_array($config))
                throw new \Exception ($config . ' not a valid db config');
            // Load database
            $db = new Database($config);

            self::$db[$this->name] = $db;
            self::$prefix = isset($config['prefix']) ? $config['prefix'] : '';
        }

        return $this->getDb();
    }

    /**
     * @return \Tiny\Database
     */
    public function getDb()
    {
        return self::$db[$this->name];
    }

    /**
     * 读操作，从库操作用query
     */
    public function query($sql, $param = array(), $fetchAll = true)
    {
        if ($fetchAll)
            $result = $this->getDb()->query($sql, $param)->fetchAll(\PDO::FETCH_OBJ);
        else
            $result = $this->getDb()->query($sql, $param)->fetch(\PDO::FETCH_OBJ);

        if($result){
            $this->_resetOperate(); // 清除options操作记录和绑定变量数组
        }
        return $result;
    }

    /**
     * 写操作，主库操作用execute
     */
    public function execute($sql, $param = array())
    {
        $result = $this->getDb()->execute($sql, $param);

        if($result) $this->_resetOperate(); // 清除options操作记录和绑定变量数组
        return $result;
    }

    private function _resetOperate()
    {
        $this->options = array();
        $this->prepareParam = array();
    }

    /**
     * 查找一个数据集合，用链式操作
     */
    public function find()
    {
        return $this->query($this->_parseSql(), $this->prepareParam);
    }

    /**
     * // For example:
     * $model->increment('foo', 1);
     * // Similarly:
     * $model->increment(['foo' => -1, 'bar' => 5]);
     */
    public function increment($increment = null, $value = null)
    {
        if(!isset($this->options['increment']))  $this->options['increment'] = array();

        if(is_string($increment) && is_numeric($value))
            $this->options['increment'][$increment] = $value;
        elseif(is_array($increment) && is_null($value))
            foreach($increment as $k => $v)
                if(is_string($k) && is_numeric($v))
                    $this->options['increment'][$k] = $v;
        return $this;
    }

    /**
     * 按主键查找一条记录，用链式操作
     */
    public function findOne($id = NULL)
    {
        $this->options['limit']['0'] = 1;
        if (!is_null($id)) {
            $this->options['where']['0'] = $this->key . ' = ?';
            $this->options['where']['1'] = array($id);
        }
        return $this->query($this->_parseSql(), $this->prepareParam, false);
    }

    /**
     * 增加记录，支持传data数组或__set
     * @param array $data data array
     * @param boolean $replace if use replace into
     * @return int insert id
     */
    public function save($data = NULL, $replace = false)
    {
        if (is_null($data)) $data = $this->_data;
        if (!$data) return NULL;

        $this->createdAt && $data['created_at'] = $this->_getTime();
        $this->updatedAt && $data['updated_at'] = $this->_getTime();

        $columns = implode('`, `', array_keys($data));

        $type = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        $sql = $type . ' ' . $this->getTableName() . ' (`' . $columns . '`) VALUES (' . rtrim(str_repeat('?, ', count($data)), ', ') . ')';

        return $this->execute($sql, array_values($data)) ? $this->getDb()->pdo['master']->lastInsertId() : NULL;
    }

    /**
     * 批量插入到数据库
     */
    public function batchSave($data = null)
    {
        if (is_null($data)) $data = $this->_data;
        if (!$data) return NULL;

        $one = current($data);
        $num = count($one);
        $columns = implode('`, `', array_keys($one));
        $this->createdAt && $columns .= '`, `created_at`, `';
        $this->updatedAt && $columns .= '`, `updated_at`, `';

        $sql = 'INSERT INTO' . ' ' . $this->getTableName() . ' (`' . $columns . '`) VALUES ';
        $param = array();
        foreach($data as $v){
            $sql .= '(' . rtrim(str_repeat('?, ', $num), ', ');
            $this->createdAt && $sql .= ", '{$this->_getTime()}'";
            $this->updatedAt && $sql .= ", '{$this->_getTime()}'";
            $sql .= '), ';
            $param = array_merge($param, array_values($v));
        }
        $sql = rtrim($sql, ', ');
        return $this->execute($sql, $param) ? $this->getDb()->pdo['master']->lastInsertId() : NULL;
    }

    /**
     * 修改记录，支持传data数组或__set
     * @param array $data data array
     * @param boolean $all if update all record
     * @return mixed affect num rows
     */
    public function update($data = null, $all = false)
    {
        if (is_null($data)) $data = $this->_data;
        if (!$data && !isset($this->options['increment'])) return NULL;

        $this->updatedAt && $data['updated_at'] = $this->_getTime();

        if($data)
            $columns = '`' . implode('` = ?, `', array_keys($data)). '` = ?,';
        else
            $columns = '';

        $increment = '';
        if(isset($this->options['increment']) && $this->options['increment']) {
            foreach ($this->options['increment'] as $field => $value)
                $increment .= "`{$field}` = `$field` + {$value},";
            $increment = rtrim($increment, ',');
        }else{
            $columns = rtrim($columns, ',');
        }

        $sql = 'UPDATE ' . $this->getTableName() . ' SET ' . $columns . $increment . ' WHERE ';

        $params = array();
        // 如果有设置主键
        if (isset($data[$this->key])) {
            $where = $this->key . ' = ?';
            $params = array($data[$this->key]);
        } elseif (isset($this->options['where']['0'])) { // 或者有设置where
            $where = $this->options['where']['0'];
            if (isset($this->options['where']['1'])) $params = $this->options['where']['1'];
        } elseif ($all) { // 更新所有数据，慎用！
            $where = '1 = 1';
        } else {
            return NULL;
        }

        if ($statement = $this->execute($sql . $where, $data ? array_merge(array_values($data), $params) : $params))
            return $statement->rowCount();
        else
            return NULL;
    }

    /**
     * 删除数据，支持主键删除或where条件
     * @param int $id key id
     * @param boolean $all if update all record
     * @return mixed affect num rows
     */
    public function delete($id = NULL, $all = false)
    {
        $params = array();
        // 如果有指定id
        if (!is_null($id)) {
            $where = $this->key . ' = ?';
            $params = array($id);
        } elseif (isset($this->options['where']['0'])) { // 或者有设置where
            $where = $this->options['where']['0'];
            if (isset($this->options['where']['1'])) $params = $this->options['where']['1'];
        } elseif ($all) // 删除所有数据，慎用！
            $where = '1 = 1';
        else
            return NULL;

        $sql = 'DELETE FROM ' . $this->getTableName() . ' WHERE ' . $where;
        if ($statement = $this->execute($sql, $params))
            return $statement->rowCount();
        else
            return NULL;
    }

    /**
     * field()无效，不指定column时count(*)，指定类似count('column_name'); // 不要加别名！
     */
    private function _count($method, $column = NULL)
    {
        $this->options['limit']['0'] = 1;
        if($method == 'distinct')
            if(is_null($column)) // 默认id
                $this->options['field']['0'] = 'count(' . $method . ' `' . $this->key . '`) as count';
            else
                $this->options['field']['0'] = 'count(' . $method . ' `' . $column . '`) as count';
        elseif(is_null($column) && $method == 'count') // 默认*
            $this->options['field']['0'] = $method . '(*) as count';
        elseif($column)
            $this->options['field']['0'] = $method . '(`' . $column . '`) as count';
        else // 默认id
            $this->options['field']['0'] = $method . '(`' . $this->key . '`) as count';
        $result = $this->query($this->_parseSql(), $this->prepareParam, false);
        return isset($result->count) ? $result->count : 0;
    }

    private function _parseSql()
    {
        return str_replace(
            array('%TABLE%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
            array(
                $this->_parseMethod('table'),
                $this->_parseMethod('field'),
                $this->_parseMethod('join'),
                $this->_parseMethod('where'),
                $this->_parseMethod('group'),
                $this->_parseMethod('having'),
                $this->_parseMethod('order'),
                $this->_parseMethod('limit'),
            ), $this->selectSql);
    }

    private function _parseMethod($method)
    {
        switch ($method) {
            case 'table':
                if(isset($this->options['alias']['0'])) // 有别名时table无效
                    $this->options[$method]['0'] = '`' . $this->table . '` ' . $this->options['alias']['0'];
                else
                    if (!isset($this->options[$method]['0'])) // 默认model名为表名
                        $this->options[$method]['0'] = '`'. $this->table . '`';
                return $this->_parseReturn($method);
            case 'field':
                if (!isset($this->options[$method]['0'])) $this->options[$method]['0'] = '*';
                elseif(is_array($this->options[$method]['0'])){ // array，不支持别名，别名用string
                    $tmp = array();
                    foreach($this->options[$method]['0'] as $v)
                        $tmp[] = '`'. $v . '`';
                    $this->options[$method]['0'] = implode(', ', $tmp);
                }else // string
                    isset($this->options[$method]['1']) && $this->_addPrepareParam($this->options[$method]['1']);
                return $this->_parseReturn($method);
            case 'alias':
                break;
            case 'join':
                $join = '';
                if(isset($this->options[$method])){
                    foreach($this->options[$method] as $v){
                        $type = isset($v['1']) ? $v['1'] : 'INNER';
                        $join .= $this->_parseReturn($method, $type . ' JOIN', $v['0']);
                    }
                }
                return $join;
            case 'where':
                if(isset($this->options[$method]['0']) && is_array($this->options[$method]['0'])){ // array，不支持别名，别名用string；只支持绑定条件变量
                    $tmp = array();
                    foreach($this->options[$method]['0'] as $k => $v){
                        $tmp[] = '`' . $k . "` = ?";
                        $this->_addPrepareParam(array($v));
                    }
                    $this->options[$method]['0'] = implode(' and ', $tmp);
                }else // string
                    isset($this->options[$method]['1']) && $this->_addPrepareParam($this->options[$method]['1']);

                return $this->_parseReturn($method, 'WHERE');
            case 'group':
                $this->_escape($method, 0);
                return $this->_parseReturn($method, 'GROUP BY');
            case 'having':
                $this->_escape($method, 0);
                return $this->_parseReturn($method, 'HAVING');
            case 'order':
                $this->_escape($method, 0);
                return $this->_parseReturn($method, 'ORDER BY');
            case 'limit':
                if(isset($this->options[$method]['0'])){
                    if(isset($this->options[$method]['1'])){ // limit(1, 2);型
                        $this->options[$method]['0'] = intval($this->options[$method]['0']) . ', ' . intval($this->options[$method]['1']);
                        unset($this->options[$method]['1']);
                    }else // limit('1, 2');型
                        $this->options[$method]['0'] = implode(',', array_map('intval', explode(',', $this->options[$method]['0'])));
                }

                return $this->_parseReturn($method, 'LIMIT');
        }
    }

    private function _escape($method, $index)
    {
        if(isset($this->options[$method][$index])){
            $this->options[$method][$index] = str_replace(
                array('\\', "\0", "\n", "\r", "'", '"', "\x1a"),
                array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
                $this->options[$method][$index]
            );
        }
    }

    private function _addPrepareParam($value)
    {
        if (isset($value))
            $this->prepareParam = array_merge($this->prepareParam, $value);
    }

    // $options为是否指定options的string内容，用在允许多次执行里面
    private function _parseReturn($method, $operate = '', $options = null)
    {
        if ($operate) $operate = ' ' . $operate . ' ';
        $string = (is_null($options) && isset($this->options[$method]['0'])) ? $this->options[$method]['0'] : $options;
        if (isset($string)) {
            if (in_array($method, array('table', 'join'))) {
                $config = Config::config()->{$this->name};
                $prefix = $config['prefix'];
                unset($config);
                $string = preg_replace_callback('/__([A-Z_-]+)__/sU', function ($match) use ($prefix) {
                    return '`' . $prefix . strtolower($match[1]) . '`';
                }, $string);
            }
            return $operate . $string;
        } else
            return '';
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

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getLastSql()
    {
        $db = $this->getDb();
        return $db::$lastQuery;
    }

    private function _getTime()
    {
        if($this->timeType == 'timestamp')
            return date('Y-m-d H:i:s');
        else
            return time();
    }

    public function getQueries()
    {
        $db = $this->getDb();
        return $db::$queries;
    }

    public static function attributes(){
        return array();
    }
}