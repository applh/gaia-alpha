<?php

namespace Mail\Model;

class Newsletter
{
    public $id;
    public $subject;
    public $content_md;
    public $content_html;
    public $status;
    public $sent_at;
    public $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->subject = $data['subject'] ?? '';
        $this->content_md = $data['content_md'] ?? '';
        $this->content_html = $data['content_html'] ?? '';
        $this->status = $data['status'] ?? 'draft';
        $this->sent_at = $data['sent_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
}
