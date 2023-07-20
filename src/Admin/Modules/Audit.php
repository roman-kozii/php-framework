<?php

namespace Nebula\Admin\Modules;
use Nebula\Admin\Module;

class Audit extends Module
{
    public function __construct(private $module_id = null)
    {
        $this->route = "audit";
        $this->parent = "Administration";
        $this->icon = "check-circle";
        $this->edit_enabled = $this->destroy_enabled = $this->create_enabled = false;

        $this->table = [
            "id" => "ID",
            "user_id" => "User",
            "table_name" => "Table",
            "table_id" => "Table ID",
            "field" => "Field",
            "old_value" => "Old Value",
            "new_value" => "New Value",
            "message" => "Message",
            "created_at" => "Created At",
        ];

        parent::__construct($module_id);
    }
}
