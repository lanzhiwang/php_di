<?php
class Bim
{
    public function doSomething()
    {
        echo __METHOD__, '|';
    }
}

class Bar
{
    private $bim;

    public function __construct(Bim $bim)
    {
        $this->bim = $bim;
    }

    public function doSomething()
    {
        $this->bim->doSomething();
        echo __METHOD__, '|';
    }
}

class Foo
{
    private $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function doSomething()
    {
        $this->bar->doSomething();
        echo __METHOD__;
    }
}

class Container
{
    private $s = array();

    public function __set($k, $c)
    {
        $this->s[$k] = $c;
    }

    public function __get($k)
    {
        // return $this->s[$k]($this);
        $result = $this->build($this->s[$k]);
        return $result;
    }

    public function getS () {
        return $this->s;
    }

    /**
     * 自动绑定（Autowiring）自动解析（Automatic Resolution）
     *
     * @param string $className
     * @return object
     * @throws Exception
     */
    public function build($className)
    {
        // 如果是匿名函数（Anonymous functions），也叫闭包函数（closures）
        //var_dump($className instanceof Closure);// bool(true)
        if ($className instanceof Closure) {
            // 执行闭包函数，并将结果
            //var_dump($className);
            //print_r($this);
            /*
             * object(Closure)#2 (1) {
                  ["parameter"]=>
                  array(1) {
                    ["$c"]=>
                    string(10) "<required>"
                  }
                }

            function ($c) {
                $bar = $c->bar;
                return new Foo($bar);
            };





                Container Object
                (
                    [s:Container:private] => Array
                        (
                            [bar] => Bar
                            [foo] => Closure Object
                                (
                                    [parameter] => Array
                                        (
                                            [$c] => <required>
                                        )

                                )

                        )

                )

             *
             */
            $o = $className($this);
            return $o;
        }

        /** @var ReflectionClass $reflector */
        $reflector = new ReflectionClass($className);

        // 检查类是否可实例化, 排除抽象类abstract和对象接口interface
        if (!$reflector->isInstantiable()) {
            throw new Exception("Can't instantiate this.");
        }

        /** @var ReflectionMethod $constructor 获取类的构造函数 */
        $constructor = $reflector->getConstructor();

        // 若无构造函数，直接实例化并返回
        if (is_null($constructor)) {
            return new $className;
        }

        // 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
        $parameters = $constructor->getParameters();

        // 递归解析构造函数的参数
        $dependencies = $this->getDependencies($parameters);

        // 创建一个类的新实例，给出的参数将传递到类的构造函数。
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];

        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            /** @var ReflectionClass $dependency */
            $dependency = $parameter->getClass();

            if (is_null($dependency)) {
                // 是变量,有默认值则设置默认值
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                // 是一个类，递归解析
                $dependencies[] = $this->build($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws Exception
     */
    public function resolveNonClass($parameter)
    {
        // 有默认值则返回默认值
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception('I have no idea what to do here.');
    }
}

// ----
$c = new Container();
//print_r($c);
/*
 * Container Object
    (
        [s:Container:private] => Array
            (
            )

    )
 */
$c->bar = 'Bar';
$c->foo = function ($c) {
    print_r($c);
    /*
     * Container Object
        (
            [s:Container:private] => Array
                (
                    [bar] => Bar
                    [foo] => Closure Object
                        (
                            [parameter] => Array
                                (
                                    [$c] => <required>
                                )

                        )

                )

        )
     *
     */
    $bar = $c->bar;
    return new Foo($bar);
};
//print_r($c->getS());
/*
 * Array
    (
        [bar] => Bar
        [foo] => Closure Object
            (
                [parameter] => Array
                    (
                        [$c] => <required>
                    )

            )

    )

 *
 */

// 从容器中取得Foo
$foo = $c->foo;
print_r($foo);
/*
 * Foo Object
    (
        [bar:Foo:private] => Bar Object
            (
                [bim:Bar:private] => Bim Object
                    (
                    )

            )

    )

 *
 */
exit;
$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething

// ----
$di = new Container();

$di->foo = 'Foo';

/** @var Foo $foo */
$foo = $di->foo;

var_dump($foo);
/*
Foo#10 (1) {
  private $bar =>
  class Bar#14 (1) {
    private $bim =>
    class Bim#16 (0) {
    }
  }
}
*/

$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething



