<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-14
 * Time: 下午3:47
 */
namespace Tiny;

class DataTable
{
    public $model = null;
    public $setting = array();
    public $addOn = array();
    public $join = array();
    private $getParam = array('filter', 'sort', 'like', 'page');
    public $get = array();

    public function __construct($model, $setting, $addOn, $join)
    {
        $this->model = $model;
        $this->setting = $setting;
        $this->addOn = $addOn;
        $this->join = $join;

        return $this->render();
    }

    private function _renderWhere()
    {
        if(isset($this->setting['column'])){
            foreach ($this->setting['column'] as $detail) {
                if (isset($detail['filter']) && isset($this->get['filter'][$detail['name']])) {
                    // filter 自动类型处理
                    switch ($detail['filter']['type']) {
                        case 'date_range':
                            $dateRange = explode('~', $this->get['filter'][$detail['name']]);
                            $this->model = call_user_func_array(
                                array($this->model, 'whereBetween'),
                                array($detail['name'], array($dateRange['0'], $dateRange['1']))
                            );
                            break;
                        case 'range':
                            $this->model = call_user_func_array(
                                array($this->model, 'whereBetween'),
                                array(
                                    $detail['name'],
                                    array(
                                        $this->get['filter'][$detail['name']]['0'],
                                        $this->get['filter'][$detail['name']]['1']
                                    )
                                )
                            );
                            break;
                        case 'input': // 只有input可进行like处理
                            if(isset($this->get['like'][$detail['name']]))
                                $operator = 'like';
                            else
                                $operator = '=';
                            $this->model = call_user_func_array(
                                array($this->model, 'where'),
                                array($detail['name'], $operator, $this->get['filter'][$detail['name']])
                            );
                            break;
                        default:
                            $this->model = call_user_func_array(
                                array($this->model, 'where'),
                                array($detail['name'], $this->get['filter'][$detail['name']])
                            );
                    }
                }

            }
        }
        // 默认过滤参数
        if(isset($this->setting['default']['filter'])){
            foreach($this->setting['default']['filter'] as $method => $rules){
                $this->model = call_user_func_array(
                    array($this->model, $method),
                    $rules
                );
            }
        }
    }

    private function _renderSort()
    {
        if(isset($this->get['sort'])){
            foreach($this->get['sort'] as $name => $rule){
                if(in_array($rule, array('asc', 'desc'))){
                    $this->model = call_user_func_array(
                        array($this->model, 'orderBy'),
                        array($name, $rule)
                    );
                }
            }
        }
    }

    private function _renderPage()
    {
        $this->model = $this->model->paginate(intval($this->get['page']['per_page']));
    }

    public function render()
    {
        $this->_getParam();
        $this->_renderWhere();
        $this->_renderSort();
        var_dump($this->model->get(), \Illuminate\Support\Facades\DB::getQueryLog());
    }

    private function _getParam()
    {
        $request = new Request();
        $get = $request->get();

        foreach($this->getParam as $param){
            $this->get[$param] = isset($get[$param]) ? $get[$param] : array();
        }
    }
}