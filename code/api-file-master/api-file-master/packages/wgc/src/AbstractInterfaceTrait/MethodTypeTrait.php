<?php

namespace WGCYunPay\AbstractInterfaceTrait;


trait MethodTypeTrait
{
    /**
     * 操作类型
     * @var string
     */
    protected $methodType = null;

    public function setMethodType($type)
    {
        if(!in_array($type, self::METHOD_ARR))
        {
            throw new \Exception('method type error');
        }
        $this->methodType = $type;
        return $this;
    }
}
