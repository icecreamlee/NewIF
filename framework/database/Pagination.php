<?php

namespace Framework\Database;

use Framework\App;
use Model;

/**
 * @deprecated
 * Class Pagination
 */
class Pagination {
    /**每页条数*/
    public $numperpage = 5;
    /**每页展现的链接数目*/
    public $linknum = 7;
    /**页面链接的开始页数*/
    public $startpage = 1;
    /**页面链接的结束页数*/
    public $endpage = 7;
    /**是否出现左侧省略条*/
    public $needleftgap = false;
    /**是否出现右侧省略条*/
    public $needrightgap = false;
    /**当前页数*/
    public $pagenum = 1;
    /**总条数*/
    public $totalnum = 0;
    public $total = 0;
    /**开始条数*/
    public $stratnum = 0;
    /**开始数 从1开始*/
    public $start = 0;
    /**结束数*/
    public $end = 0;
    /**总页数*/
    public $totalpage = 0;
    /**是否是第一页*/
    public $isfirst = false;
    /**是否是最后一页*/
    public $islast = false;
    /**通用地址*/
    public $commonlink;
    /**首页地址*/
    public $firstlink;
    /**末页地址*/
    public $lastlink;
    /**上一页地址*/
    public $prevlink;
    /**下一页地址*/
    public $nextlink;
    /**get的Query序列*/
    public $querystr;
    /**当只有一页是是否仍要显示分页*/
    public $showifone;

    /**
     * 分页程序初始化
     * @param int $numperpage 每页条数
     * @param int $linknum 每页展现的链接数目
     * @param bool $showifone 当只有一页是是否仍要显示分页
     */
    function __construct($numperpage = 20, $linknum = 9, $showifone = true) {
        $this->numperpage = $numperpage;
        $this->linknum = $linknum;
        $this->showifone = $showifone;
        if ($_GET['YYUC_PAGE_jumptxt']) {
            $this->pagenum = intval($_GET['YYUC_PAGE_jumptxt']);
            unset($_GET['YYUC_PAGE_jumptxt']);
        } elseif (isset($_SERVER['PAGING_NUM'])) {
            $this->pagenum = intval($_SERVER['PAGING_NUM']);
        }
        $GLOBALS['P'] = $this;
        //解析query
        $this->querystr = '';
        if ($_GET) {
            $query_arr = array();
            foreach ($_GET as $k => $v) {
                if (!is_int($k)) {
                    $query_arr[] = $k . '=' . urlencode($v);
                }

            }
            if (count($query_arr) > 0) {
                $this->querystr = '?' . implode('&', $query_arr);
            }
        }
    }

    /**
     * 基于Model分页的查询
     * @param Model $model 模型
     * @return mixed
     */
    function model_list($model, $back_array = false) {
        $db = DB::get_db();
        $sql = $db->com_sql($model->YYUCSYS_real_tablename, $model->YYUCSYS_condition, null, null, "count(*) c", $model->YYUCSYS_pam);
        $this->_transpagination($db->query($sql[0], $sql[1]));
        $model->limit($this->stratnum . ',' . $this->numperpage);
        if ($back_array) {
            return $model->list_all_array();
        } else {
            return $model->list_all();
        }
    }

    /**
     * 基于SQL语句的分页的查询
     * @param string $sql 分页SQl
     * @param array $pam 分页条件
     * @param bool $back_model
     * @return array
     * @deprecated
     */
    function sql_list($sql, $pam = [], $back_model = false) {
        $db = DB::get_db();
        $pos = mb_stripos($sql, ' from ');
        $this->_transpagination($db->query("select count(*) c " . mb_substr($sql, $pos), $pam));
        // if ($back_model) {
        // return $db->query_model($sql.' limit '.$this->stratnum.','.$this->numperpage,$pam);
        // } else {
        return $db->query($sql . ' limit ' . $this->stratnum . ',' . $this->numperpage, $pam);
        // }
    }

    /**
     * 基于查询结果总数的分页
     * @param $total_num
     */
    function num_list($total_num) {
        $this->_transpagination(array(array('c' => $total_num)));
    }

    /**
     * 进行分页信息计算
     * @param $res 总数查询结果集
     */
    function _transpagination($res) {
        $this->totalnum = intval($res[0]['c']);        //总页数
        if ($this->totalnum == 0) {
            $this->totalpage = 1;
        } else {
            $this->totalpage = intval(($this->totalnum - 1) / $this->numperpage) + 1;
        }
        if ($this->pagenum > $this->totalpage) {
            $this->pagenum = $this->totalpage;
        } else if ($this->pagenum < 1) {
            $this->pagenum = 1;
        }
        //开始条数
        $this->stratnum = ($this->pagenum - 1) * $this->numperpage;

        //供页面调用属性
        $this->total = strval($this->totalnum);
        $this->start = strval($this->stratnum + 1);
        $this->end = strval(($this->stratnum + $this->numperpage > $this->totalnum) ? $this->totalnum : $this->stratnum + $this->numperpage);


        if ($this->pagenum == 1) {
            $this->isfirst = true;
        }
        if ($this->pagenum == $this->totalpage) {
            $this->islast = true;
        }
        $this->commonlink = '/' . $_SERVER['NO_PAGINATION_URI'];
        //首页地址
        $this->firstlink = ($this->commonlink . App::config('url_suffix')) . $this->querystr;
        //末页地址
        $this->lastlink = (($this->totalpage == 1 ? $this->commonlink : $this->commonlink . config('paging_separate') . $this->totalpage) . App::config('url_suffix')) . $this->querystr;
        //上一页地址
        $this->prevlink = (($this->pagenum < 3 ? $this->commonlink : $this->commonlink . config('paging_separate') . ($this->pagenum - 1)) . App::config('url_suffix')) . $this->querystr;
        //下一页地址
        $this->nextlink = (($this->pagenum == $this->totalpage ? $this->lastlink : $this->commonlink . config('paging_separate') . ($this->pagenum + 1)) . App::config('url_suffix')) . $this->querystr;
        //每侧出现的链接数
        $halflinks = intval(($this->linknum - 1) / 2);
        if ($this->pagenum - 1 <= $halflinks) {
            $this->needleftgap = false;
            if ($this->totalpage < $this->linknum) {
                $this->endpage = $this->totalpage;
                $this->needrightgap = false;
            } else {
                $this->endpage = $halflinks + $this->pagenum;
                $this->needrightgap = true;
            }
        } else if ($this->totalpage - $this->pagenum <= $halflinks) {
            $this->needrightgap = false;
            $this->endpage = $this->totalpage;
            $this->startpage = $this->totalpage - $this->linknum + 1;
            if ($this->startpage < 2) {
                $this->startpage = 1;
                $this->needleftgap = false;
            } else {
                $this->needleftgap = true;
            }
        } else {
            $this->needleftgap = true;
            $this->needrightgap = true;
            $this->startpage = $this->pagenum - $halflinks;
            $this->endpage = $this->pagenum + $halflinks;
        }
    }
}
