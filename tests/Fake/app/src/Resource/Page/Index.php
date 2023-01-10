<?php

declare(strict_types=1);

namespace MyVendor\MyProject\Resource\Page;

use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    public $code = 301;
    public $headers = [
        'Location' => '/halo'
    ];

    public function onGet()
    {
        $this->body = ['method' => __FUNCTION__];

        return $this;
    }

    public function onPost()
    {
        $this->body = ['method' => __FUNCTION__];

        return $this;
    }

    public function onPut()
    {
        $this->body = ['method' => __FUNCTION__];

        return $this;
    }

    public function onPatch()
    {
        $this->body = ['method' => __FUNCTION__];

        return $this;
    }

    public function onDelete()
    {
        $this->body = ['method' => __FUNCTION__];

        return $this;
    }
}
