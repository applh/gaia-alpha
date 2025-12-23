<?php

namespace Drawing\Model;

class Drawing
{
    public $id;
    public $title;
    public $description;
    public $content;
    public $level;
    public $background_image;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->content = $data['content'] ?? null;
        $this->level = $data['level'] ?? 'beginner';
        $this->background_image = $data['background_image'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
