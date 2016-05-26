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
        return $this->build($this->s[$k]);
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
    public function build($className, $flag = false)
    {
        echo "\n\n";
        echo "--------------------处理{$className}--------------------------------------------------------------------------\n";
        if ($flag) {
            echo "递归解析{$className}\n";
        } else {
            echo "非递归解析{$className}\n";
        }

        // 如果是匿名函数（Anonymous functions），也叫闭包函数（closures）
        echo "显示是否是instanceof Closure\n";
        var_dump($className instanceof Closure);
        if ($className instanceof Closure) {
            // 执行闭包函数，并将结果
            return $className($this);
        }

        /** @var ReflectionClass $reflector */
        $reflector = new ReflectionClass($className);
        echo "显示ReflectionClass\n";
        var_dump($reflector);

        // 检查类是否可实例化, 排除抽象类abstract和对象接口interface
        echo "检查类是否可实例化, 排除抽象类abstract和对象接口interface\n";
        var_dump($reflector->isInstantiable());
        if (!$reflector->isInstantiable()) {
            throw new Exception("Can't instantiate this.");
        }

        /** @var ReflectionMethod $constructor 获取类的构造函数 */
        $constructor = $reflector->getConstructor();
        echo "获取类的构造函数\n";
        var_dump($constructor);

        // 若无构造函数，直接实例化并返回
        if (is_null($constructor)) {
            echo "若无构造函数，直接实例化并返回\n";
            $o = new $className;
            var_dump($o);
            return $o;
        }

        // 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
        $parameters = $constructor->getParameters();
        echo "取构造函数参数,通过 ReflectionParameter 数组返回参数列表\n";
        var_dump($parameters);

        // 递归解析构造函数的参数
        echo "<<<<开始>>>>递归解析构造函数的参数\n";
        $dependencies = $this->getDependencies($parameters);
        echo "递归结束\n";
        var_dump($dependencies);

        // 创建一个类的新实例，给出的参数将传递到类的构造函数。
        echo "创建一个类的新实例，给出的参数将传递到类的构造函数\n";
        $b = $reflector->newInstanceArgs($dependencies);
        var_dump($b);
        return $b;
    }

    /**
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters)
    {
        echo "--------在方法getDependencies中--------\n";
        echo "显示参数\n";
        var_dump($parameters);
        $dependencies = [];

        /** @var ReflectionParameter $parameter */
        echo "进入循环\n";
        foreach ($parameters as $parameter) {
            var_dump($parameter);
            /** @var ReflectionClass $dependency */
            $dependency = $parameter->getClass();
            var_dump($dependency);
            var_dump(is_null($dependency));
            if (is_null($dependency)) {
                // 是变量,有默认值则设置默认值
                echo "是变量,有默认值则设置默认值\n";
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                // 是一个类，递归解析
                echo "是一个类，递归解析\n";
                $dependencies[] = $this->build($dependency->name, true);
            }
        }
        echo "方法getDependencies结束\n";
        echo "返回值\n";
        var_dump($dependencies);

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
$di = new Container();

$di->foo = 'Foo';
//print_r($di->getS());
/*
 * Array
(
    [foo] => Foo
)

 *
 */

/** @var Foo $foo */
$foo = $di->foo;
echo "\n\n\n";
var_dump($foo);
/*
object(Foo)#5 (1) {
  ["bar":"Foo":private]=>
  object(Bar)#9 (1) {
    ["bim":"Bar":private]=>
    object(Bim)#11 (0) {
    }
  }
}

*/
echo "整个处理结束\n";
$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething
echo "\n\n";

/*
ubuntu@huzhi:~/webroot/www/MailEye/Shell$ php step10.php


--------------------处理Foo--------------------------------------------------------------------------
非递归解析Foo
显示是否是instanceof Closure
bool(false)
显示ReflectionClass
object(ReflectionClass)#2 (1) {
  ["name"]=>
  string(3) "Foo"
}
检查类是否可实例化, 排除抽象类abstract和对象接口interface
bool(true)
获取类的构造函数
object(ReflectionMethod)#3 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(3) "Foo"
}
取构造函数参数,通过 ReflectionParameter 数组返回参数列表
array(1) {
  [0]=>
  &object(ReflectionParameter)#4 (1) {
    ["name"]=>
    string(3) "bar"
  }
}
<<<<开始>>>>递归解析构造函数的参数
--------在方法getDependencies中--------
显示参数
array(1) {
  [0]=>
  &object(ReflectionParameter)#4 (1) {
    ["name"]=>
    string(3) "bar"
  }
}
进入循环
object(ReflectionParameter)#4 (1) {
  ["name"]=>
  string(3) "bar"
}
object(ReflectionClass)#5 (1) {
  ["name"]=>
  string(3) "Bar"
}
bool(false)
是一个类，递归解析


--------------------处理Bar--------------------------------------------------------------------------
递归解析Bar
显示是否是instanceof Closure
bool(false)
显示ReflectionClass
object(ReflectionClass)#6 (1) {
  ["name"]=>
  string(3) "Bar"
}
检查类是否可实例化, 排除抽象类abstract和对象接口interface
bool(true)
获取类的构造函数
object(ReflectionMethod)#7 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(3) "Bar"
}
取构造函数参数,通过 ReflectionParameter 数组返回参数列表
array(1) {
  [0]=>
  &object(ReflectionParameter)#8 (1) {
    ["name"]=>
    string(3) "bim"
  }
}
<<<<开始>>>>递归解析构造函数的参数
--------在方法getDependencies中--------
显示参数
array(1) {
  [0]=>
  &object(ReflectionParameter)#8 (1) {
    ["name"]=>
    string(3) "bim"
  }
}
进入循环
object(ReflectionParameter)#8 (1) {
  ["name"]=>
  string(3) "bim"
}
object(ReflectionClass)#9 (1) {
  ["name"]=>
  string(3) "Bim"
}
bool(false)
是一个类，递归解析


--------------------处理Bim--------------------------------------------------------------------------
递归解析Bim
显示是否是instanceof Closure
bool(false)
显示ReflectionClass
object(ReflectionClass)#10 (1) {
  ["name"]=>
  string(3) "Bim"
}
检查类是否可实例化, 排除抽象类abstract和对象接口interface
bool(true)
获取类的构造函数
NULL
若无构造函数，直接实例化并返回
object(Bim)#11 (0) {
}
方法getDependencies结束
返回值
array(1) {
  [0]=>
  object(Bim)#11 (0) {
  }
}
递归结束
array(1) {
  [0]=>
  object(Bim)#11 (0) {
  }
}
创建一个类的新实例，给出的参数将传递到类的构造函数
object(Bar)#9 (1) {
  ["bim":"Bar":private]=>
  object(Bim)#11 (0) {
  }
}
方法getDependencies结束
返回值
array(1) {
  [0]=>
  object(Bar)#9 (1) {
    ["bim":"Bar":private]=>
    object(Bim)#11 (0) {
    }
  }
}
递归结束
array(1) {
  [0]=>
  object(Bar)#9 (1) {
    ["bim":"Bar":private]=>
    object(Bim)#11 (0) {
    }
  }
}
创建一个类的新实例，给出的参数将传递到类的构造函数
object(Foo)#5 (1) {
  ["bar":"Foo":private]=>
  object(Bar)#9 (1) {
    ["bim":"Bar":private]=>
    object(Bim)#11 (0) {
    }
  }
}



object(Foo)#5 (1) {
  ["bar":"Foo":private]=>
  object(Bar)#9 (1) {
    ["bim":"Bar":private]=>
    object(Bim)#11 (0) {
    }
  }
}
整个处理结束
Bim::doSomething|Bar::doSomething|Foo::doSomething

ubuntu@huzhi:~/webroot/www/MailEye/Shell$
*/