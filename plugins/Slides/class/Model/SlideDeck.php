<?php

namespace Slides\Model;

class SlideDeck
{
    public $id;
    public $title;
    public $author_id;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->author_id = $data['author_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
