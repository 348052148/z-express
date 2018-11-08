# z-express
php实现的一个计算表达式
#### 例
`
Express::calculate('3 * 5+(min<<12,10.5+1,3>>max)*3/4+$a',['a' => 5])
`
#### 函数 （将函数视为表达式的一个计算项 和 数字，变量等价
`
min << 1,2 或者 1,2 >> min
Express::calculate('min << 1,3')  == 1
`
#### 变量替换
`
Express::calculate('4+$a',['a' => 5])  == 9
`
