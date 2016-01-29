<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午5:24
 *
 * from micomvc
 */
namespace Tiny;

class Validation
{
    // Current field
    public $field;

    // Data to validate
    public $data;

    // Array of errors
    public $errors = '';

    // The text to put before an error
    public $error_prefix = '<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';

    // The text to put after an error
    public $error_suffix = '</div>';

    protected $tokenName = '__hash__';

    /**
     * Create the validation object using this data
     *
     * @param array $data to validate
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }


    /**
     * Add a new field to the validation object
     *
     * @param string $field name
     * @return $this
     */
    public function field($field)
    {
        $this->field = $field;
        return $this;
    }


    /**
     * Return the value of the given field
     *
     * @param string $field name to use instead of current field
     * @return mixed
     */
    public function value($field = NULL)
    {
        if( ! $field)
        {
            $field = $this->field;
        }

        if(isset($this->data[$field]))
        {
            return $this->data[$field];
        }
    }


    /**
     * Return success if validation passes!
     *
     * @return boolean
     */
    public function validates()
    {
        return ! $this->errors;
    }


    /**
     * Fetch validation error for the given field
     *
     * @param string $field name to use instead of current field
     * @param boolean $wrap error with suffix/prefix
     * @return string
     */
    public function error($field = NULL, $wrap = FALSE)
    {
        if( ! $field)
        {
            $field = $this->field;
        }

        if(isset($this->errors[$field]))
        {
            if($wrap)
            {
                return $this->error_prefix . $this->errors[$field] . $this->error_suffix;
            }

            return $this->errors[$field];
        }
    }


    /**
     * Return all validation errors as an array
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }


    /**
     * Return all validation errors wrapped in HTML suffix/prefix
     *
     * @return string
     */
    public function __toString()
    {
        $output = '';
        foreach($this->errors as $error)
        {
            $output .= $this->error_prefix . $error . $this->error_suffix . "\n";
        }
        return $output;
    }


    /**
     * Middle-man to all rule functions to set the correct error on failure.
     *
     * @param string $rule
     * @param array $args
     * @return this
     */
    public function __call($rule, $args)
    {
        if(isset($this->errors[$this->field]) OR empty($this->data[$this->field])) return $this;

        // Add method suffix
        $method = $rule . '_rule';

        // Defaults for $error, $params
        $args = $args + array(NULL, NULL);

        // If the validation fails
        if( ! $this->$method($this->data[$this->field], $args[1]))
        {
            $this->errors[$this->field] = $args[0];
        }

        return $this;
    }


    /**
     * Value is required and cannot be empty.
     *
     * @param string $error message
     * @param boolean $string set to true if data must be string type
     * @return boolean
     */
    public function required($error, $string = TRUE)
    {
        if(empty($this->data[$this->field]) OR ($string AND is_array($this->data[$this->field])))
        {
            $this->errors[$this->field] = $error;
        }

        return $this;
    }

    public function token($error)
    {
        if (Config::config()->token) {
            if (!isset($this->data[$this->tokenName]) || !Session::get($this->tokenName)) // 令牌数据无效
                $this->errors[$this->tokenName] = $error;

            // 令牌验证
            list($key, $value) = explode('_', $this->data[$this->tokenName]);
            $session = Session::get($this->tokenName);
            if ($value && isset($session[$key]) && $session[$key] === $value) // 防止重复提交
                unset($_SESSION[Config::config()->flag . '_' . $this->tokenName][$key]); // 验证完成销毁session
            else {
                unset($_SESSION[Config::config()->flag . '_' . $this->tokenName][$key]);
                $this->errors[$this->tokenName] = $error;
            }
        }
        return $this;
    }


    protected function json_rule($data, $validateEmpty = null)
    {
        $decode = json_decode($data, true);
        if(!$validateEmpty)
            return is_array($decode);
        else
            return is_array($decode) && count($decode) > 0;
    }


    /**
     * Verify value is a string.
     *
     * @param mixed $data to validate
     * @return boolean
     */
    protected function string_rule($data)
    {
        return is_string($data);
    }


    /**
     * Verify value is an array.
     *
     * @param mixed $data to validate
     * @return boolean
     */
    protected function array_rule($data)
    {
        return is_array($data);
    }


    /**
     * Verify value is an integer
     *
     * @param string $data to validate
     * @return boolean
     */
    protected function integer_rule($data)
    {
        return is_int($data) OR ctype_digit($data);
    }


    /**
     * Verifies the given date string is a valid date using the format provided.
     *
     * @param string $data to validate
     * @param string $format of date string
     * @return boolean
     */
    protected function date_rule($data, $format = NULL)
    {
        if($format)
        {
            if($data = DateTime::createFromFormat($data, $format))
            {
                return TRUE;
            }
        }
        elseif($data = strtotime($data))
        {
            return TRUE;
        }
    }


    /**
     * Condition must be true.
     *
     * @param mixed $data to validate
     * @param boolean $condition to test
     * @return boolean
     */
    protected function true_rule($data, $condition)
    {
        return $condition;
    }


    /**
     * Field must have a value matching one of the options
     *
     * @param mixed $data to validate
     * @param $options
     * @internal param array $array of posible values
     * @return boolean
     */
    protected function options_rule($data, $options)
    {
        return in_array($data, $options);
    }


    /**
     * Validate that the given value is a valid IP4/6 address.
     *
     * @param mixed $data to validate
     * @return boolean
     */
    protected function ip_rule($data)
    {
        return (filter_var($data, FILTER_VALIDATE_IP) !== false);
    }


    /**
     * Verify that the value of a field matches another one.
     *
     * @param mixed $data to validate
     * @param string $field name of the other element
     * @return boolean
     */
    protected function matches_rule($data, $field)
    {
        if(isset($this->data[$field]))
        {
            return $data === $this->data[$field];
        }
    }


    /**
     * Check to see if the email entered is valid.
     *
     * @param string $data to validate
     * @return boolean
     */
    protected function email_rule($data)
    {
        return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $data);
    }


    /**
     * Must only contain word characters (A-Za-z0-9_).
     *
     * @param string $data to validate
     * @return boolean
     */
    protected function word_rule($data)
    {
        return ! preg_match("/\W/", $data);
    }


    /**
     * Plain text that contains no HTML/XML "><" characters.
     *
     * @param string $data to validate
     * @return boolean
     */
    protected function plaintext_rule($data)
    {
        return (mb_strpos($data, '<') === FALSE AND mb_strpos($data, '>') === FALSE);
    }


    /**
     * Minimum length of the string.
     *
     * @param string $data to validate
     * @param int $length of the string
     * @return boolean
     */
    protected function min_rule($data, $length)
    {
        return mb_strlen($data) >= $length;
    }


    /**
     * Maximum length of the string.
     *
     * @param string $data to validate
     * @param int $length of the string
     * @return boolean
     */
    protected function max_rule($data, $length)
    {
        return mb_strlen($data) <= $length;
    }

    /**
     * between length of the string.
     *
     * @param string $data to validate
     * @param int $min of the string
     * @param int $max of the string
     * @return boolean
     */
    protected function between_rule($data, $min, $max)
    {
        $strlen = mb_strlen($data);
        return ($max > $min) AND ($strlen >= $min) AND ($strlen <= $max);
    }

    /**
     * Exact length of the string.
     *
     * @param string $data to validate
     * @param int $length of the string
     * @return boolean
     */
    protected function length_rule($data, $length)
    {
        return mb_strlen($data) === $length;
    }


    /**
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @param string $data to validate
     * @return boolean
     */
    protected function base64_rule($data)
    {
        return preg_match('/[^a-zA-Z0-9\/\+=]/', $data);
    }

    protected function equal_rule($field, $data)
    {
        return $field == $data;
    }

    // 返回的错误验证
    public function validateWithBack()
    {
        if($this->errors)
            Helper::backWithInputTips($this->data, null, $this->errors);
    }

}

// END
