<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-8-19
 * Time: 下午5:38
 *
 * 该类来源：http://www.oschina.net/code/snippet_162279_6530
 */
namespace Tiny;

class Cache
{
    private static $_instance = null;

    protected $_options = array(
        'cache_dir' => "./",
        'file_name_prefix' => 's_cache',
        'mode' => '1', //mode 1 为serialize model 2为保存为可执行文件
    );

    /**
     * 得到本类实例
     *
     * @return Ambiguous
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 得到缓存信息
     *
     * @param string $id
     * @return boolean|array
     */
    public static function get($id)
    {
        $instance = self::getInstance();

        //缓存文件不存在
        if (!$instance->has($id)) {
            return false;
        }

        $file = $instance->_file($id);

        $data = $instance->_fileGetContents($file);

        if ($data['expire'] == 0 || time() < $data['expire']) {
            return $data['contents'];
        }
        return false;
    }

    /**
     * 设置一个缓存
     *
     * @param string $id 缓存id
     * @param array $data 缓存内容
     * @param int $cacheLife 缓存生命 默认为0无限生命
     */
    public static function set($id, $data, $cacheLife = 0)
    {
        $instance = self::getInstance();

        $time = time();
        $cache = array();
        $cache['contents'] = $data;
        $cache['expire'] = $cacheLife === 0 ? 0 : $time + $cacheLife;
        $cache['mtime'] = $time;

        $file = $instance->_file($id);

        return $instance->_filePutContents($file, $cache);
    }

    /**
     * 清除一条缓存
     *
     * @param string cache id
     * @return void
     */
    public static function delete($id)
    {
        $instance = self::getInstance();

        if (!$instance->has($id)) {
            return false;
        }
        $file = $instance->_file($id);
        //删除该缓存
        return unlink($file);
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $id cache_id
     * @return boolean true 缓存存在 false 缓存不存在
     */
    public static function has($id)
    {
        $instance = self::getInstance();
        $file = $instance->_file($id);

        if (!is_file($file)) {
            return false;
        }
        return true;
    }

    /**
     * 通过缓存id得到缓存信息路径
     * @param string $id
     * @return string 缓存文件路径
     */
    protected function _file($id)
    {
        $instance = self::getInstance();
        $fileName = $instance->_idToFileName($id);
        return $instance->_options['cache_dir'] . $fileName;
    }

    /**
     * 通过id得到缓存信息存储文件名
     *
     * @param  $id
     * @return string 缓存文件名
     */
    protected function _idToFileName($id)
    {
        $instance = self::getInstance();
        $prefix = $instance->_options['file_name_prefix'];
        return $prefix . '---' . $id;
    }

    /**
     * 通过filename得到缓存id
     *
     * @param  $id
     * @return string 缓存id
     */
    protected function _fileNameToId($fileName)
    {
        $instance = self::getInstance();
        $prefix = $instance->_options['file_name_prefix'];
        return preg_replace('/^' . $prefix . '---(.*)$/', '$1', $fileName);
    }

    /**
     * 把数据写入文件
     *
     * @param string $file 文件名称
     * @param array $contents 数据内容
     * @return bool
     */
    protected function _filePutContents($file, $contents)
    {
        if ($this->_options['mode'] == 1) {
            $contents = serialize($contents);
        } else {
            $time = time();
            $contents = "<?php\n" .
                " // mktime: " . $time . "\n" .
                " return " .
                var_export($contents, true) .
                "\n?>";
        }

        $result = false;
        $f = @fopen($file, 'w');
        if ($f) {
            @flock($f, LOCK_EX);
            fseek($f, 0);
            ftruncate($f, 0);
            $tmp = @fwrite($f, $contents);
            if (!($tmp === false)) {
                $result = true;
            }
            @fclose($f);
        }
        @chmod($file, 0777);
        return $result;
    }

    /**
     * 从文件得到数据
     *
     * @param  sring $file
     * @return boolean|array
     */
    protected function _fileGetContents($file)
    {
        if (!is_file($file)) {
            return false;
        }

        if ($this->_options['mode'] == 1) {
            $f = @fopen($file, 'r');
            @$data = fread($f, filesize($file));
            @fclose($f);
            return unserialize($data);
        } else {
            return include $file;
        }
    }

    /**
     * 构造函数
     */
    protected function __construct()
    {

    }

    /**
     * 设置缓存路径
     *
     * @param string $path
     * @return self
     */
    public static function setCacheDir($path)
    {
        $instance = self::getInstance();
        if (!file_exists($path)) {
            mkdir($path);
        }
        if (!is_dir($path)) {
            exit('file_cache: ' . $path . ' 不是一个有效路径 ');
        }
        if (!is_writable($path)) {
            exit('file_cache: 路径 "' . $path . '" 不可写');
        }

        $path = rtrim($path, '/') . '/';
        $instance->_options['cache_dir'] = $path;

        return $instance;
    }

    /**
     * 设置缓存文件前缀
     *
     * @param srting $prefix
     * @return self
     */
    public static function setCachePrefix($prefix)
    {
        $instance = self::getInstance();
        $instance->_options['file_name_prefix'] = $prefix;
        return $instance;
    }

    /**
     * 设置缓存存储类型
     *
     * @param int $mode
     * @return self
     */
    public static function setCacheMode($mode = 1)
    {
        $instance = self::getInstance();
        if ($mode == 1) {
            $instance->_options['mode'] = 1;
        } else {
            $instance->_options['mode'] = 2;
        }

        return $instance;
    }

    /**
     * 删除所有缓存
     * @return boolean
     */
    public static function flush()
    {
        $instance = self::getInstance();
        $glob = @glob($instance->_options['cache_dir'] . $instance->_options['file_name_prefix'] . '--*');

        if (empty($glob)) {
            return false;
        }

        foreach ($glob as $v) {
            $fileName = basename($v);
            $id = $instance->_fileNameToId($fileName);
            $instance->delete($id);
        }
        return true;
    }
}
