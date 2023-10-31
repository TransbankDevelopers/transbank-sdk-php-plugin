<?php

namespace Transbank\Plugin\Model;

class DetailBase
{
    public $title;
    /**
    * @var array
    */
    public $data = [];

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function addItem($label, $value) {
        $this->data[] = ["label" => $label, "value" => $value];
    }

}
