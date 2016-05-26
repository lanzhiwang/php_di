<?php
// 代码【1】
class Bim
{
    public function doSomething()
    {
        echo __METHOD__, '|';
    }
}

class Bar
{
    public function doSomething()
    {
        $bim = new Bim();
        $bim->doSomething();
        echo __METHOD__, '|';
    }
}

class Foo
{
    public function doSomething()
    {
        $bar = new Bar();
        $bar->doSomething();
        echo __METHOD__;
    }
}

$foo = new Foo();
$foo->doSomething(); //Bim::doSomething|Bar::doSomething|Foo::doSomething