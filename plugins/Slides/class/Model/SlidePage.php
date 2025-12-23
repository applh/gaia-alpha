<?php

namespace Slides\Model;

class SlidePage
{
    public $id;
    public $deck_id;
    public $order_index;
    public $content;
    public $slide_type;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->deck_id = $data['deck_id'] ?? null;
        $this->order_index = $data['order_index'] ?? 0;
        $this->content = $data['content'] ?? null;
        $this->slide_type = $data['slide_type'] ?? 'drawing';
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
