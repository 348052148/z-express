<?php
namespace App\Libs;

class Express {
    private $s1 = [];
    private $s2 = [];
    private $stack = [];
    private $filterDelimiters;
    private $oparr = [];
    private $variables = [];

    private function __construct($express)
    {
        $this->express = $express;
        $this->filterDelimiters = [
            "\t","\n","\r\n",' '
        ];
        $this->oparr = [
            '+',
            '-',
            '*',
            '/',
            '%',
            ')',
            '('
        ];
    }

    private function setVariables($variables){
        $this->variables = array_merge($this->variables, $variables);
    }

    public static function calculate($express, $variables = [])
    {var_dump($express);
        $expressLg = new Express($express);
        $expressLg->setVariables($variables);
        return $expressLg->run();
    }

    private function run(){
        $this->express = strrev($this->express);
        foreach ($this->filterDelimiters as $v) {
            $this->express = str_replace($v,'',$this->express);
        }

        $priority = [
            '+' => 1,
            '-' => 1,
            '*' => 2,
            '/' => 2,
            '%' => 2,
        ];

        $oparr = [
            '+',
            '-',
            '*',
            '/',
            '%',
            ')',
            '('
        ];

        $model = '';
        for ($i=0; $i < strlen($this->express); $i++) {
            $model .= $this->express[$i];
            if(!in_array($this->express[$i], $oparr)) {
                if ($i + 1 < strlen($this->express) && !in_array($this->express[$i + 1], $oparr)) {
                    continue;
                }
            }
            $model = strrev($model);

            if ($model == ')') {
                array_push($this->s1, $this->express[$i]); $model ='';
                continue;
            }
            //如果是左括号“(”，则依次弹出S1栈顶的运算符，并压入S2，直到遇到右括号为止，此时将这一对括号丢弃
            if ($model == '(') {
                $c = array_pop($this->s1);
                while ($c != ')') {
                    array_push($this->s2, $c);
                    $c = array_pop($this->s1);
                }
                $model ='';
                continue;
            }

            if (in_array($model, $oparr )) { //这里多判断了( )
                while (true) {
                    if (empty($this->s1) || $this->s1[count($this->s1) - 1] == ')') {
                        array_push($this->s1, $model);
                        break;
                    } else if ($priority[$model] >= $priority[$this->s1[count($this->s1) - 1]]) {
                        array_push($this->s1, $model);
                        break;
                    } else {
                        //否则，将s1栈顶的运算符弹出并压入到s2中，再次转到(4-1)与s1中新的栈顶运算符相比较
                        array_push($this->s2, array_pop($this->s1));
                    }
                }
                $model ='';
                continue;
            }

            array_push($this->s2, $model);$model='';

        }

        while ($c = array_pop($this->s1)) {
            array_push($this->s2, $c);
        }

        foreach ($this->s2 as $v) {
            if (in_array($v, ['+','-','*','/'])) {
                switch ($v) {
                    case '+':
                        array_push($this->stack, $this->parseItem(array_pop($this->stack)) + $this->parseItem(array_pop($this->stack)));
                        break;
                    case '-':
                        array_push($this->stack, $this->parseItem(array_pop($this->stack)) - $this->parseItem(array_pop($this->stack)));
                        break;
                    case '*':
                        array_push($this->stack, $this->parseItem(array_pop($this->stack)) * $this->parseItem(array_pop($this->stack)));
                        break;
                    case '/':
                        array_push($this->stack, $this->parseItem(array_pop($this->stack)) / $this->parseItem(array_pop($this->stack)));
                        break;
                    case '%':
                        array_push($this->stack, $this->parseItem(array_pop($this->stack)) % $this->parseItem(array_pop($this->stack)));
                }
            } else {
                array_push($this->stack, $v);
            }
        }
        return array_pop($this->stack);
    }

    private function parseItem($item)
    {
        //变量
        if (preg_match('/^\$/', $item)) {
            if (!isset($this->variables[str_replace('$', '', $item)])) {
                throw new \Exception('未找到变量');
            }
            return $this->variables[str_replace('$', '', $item)];
        }

        $func = 'intval';
        $argv = [$item];
        if (strpos($item, '.')!==false) {
            $func = 'floatval';
            $argv[0] = $item;
        }

        if(strpos($item, '>>') !== false){
            $itemParams = explode('>>', $item);
            if (!function_exists($itemParams[1])) {
                throw new \Exception('系统函数未找到');
            }
            $func = $itemParams[1];
            $argv = explode(',', $itemParams[0]);
        }
        if(strpos($item, '<<') !== false){
            $itemParams = explode('<<', $item);
            if (!function_exists($itemParams[0])) {
                throw new \Exception('系统函数未找到');
            }
            $func = $itemParams[0];
            $argv = explode(',', $itemParams[1]);
        }

        //如果是最原始的语义-则不进行参数解析
        if (!in_array($func, ['intval', 'floatval']) && is_array($argv)) {
            array_walk($argv, function (&$v) {
                $v = $this->parseItem($v);
            });
        }

        return call_user_func_array($func, $argv);
    }

}
