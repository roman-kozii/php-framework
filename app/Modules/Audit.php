<?php

namespace App\Modules;

use Nebula\Alerts\Flash;
use Nebula\Backend\Module;

class Audit extends Module
{
    public function __construct()
    {
        $this->module_icon = "book-open";
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->name_col = "id";
        $this->table_columns = [
            "audit.id" => "ID",
            "users.name" => "User",
            "audit.table_name" => "Table",
            "audit.table_id" => "ID",
            "audit.field" => "Field",
            "audit.old_value" => "Old",
            "'🠮' as sep" => "",
            "audit.new_value" => "New",
            "audit.message" => "Message",
            "audit.created_at" => "Created At",
        ];
        $this->joins = ["INNER JOIN users ON audit.user_id = users.id"];
        $this->search = ["table_name", "table_id", "field", "name"];
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "All" => "1=1",
        ];
        $this->addRowAction("undo_change", "Undo", "Are you sure you want to restore this value?");
        parent::__construct("audit", "audit");
    }

    protected function hasRowActionPermission(string $name, string $id): bool
    {
        $row = db()->select("SELECT * FROM $this->table_name WHERE $this->key_col = ?", $id);
        if (in_array($row->message, ['UPDATE', 'UNDO'])) return true;
        return false;
    }

    protected function processTableRequest(): void
    {
        parent::processTableRequest();
        if (request()->has("undo_change")) {
            $row = db()->select("SELECT * FROM $this->table_name WHERE $this->key_col = ?", request()->id);
            $result = db()->query("UPDATE $row->table_name SET $row->field = ? WHERE id = ?", $row->old_value, $row->id);
            if ($result) {
                Flash::addFlash(
                    "success",
                    "Old value restored successfully"
                );
                $this->audit(user()->id, $row->table_name, $row->table_id, $row->field, $row->old_value, "UNDO");
            } else {
                Flash::addFlash(
                    "warning",
                    "Oops! Couldn't undo change for this record"
                );
            }
            request()->remove("undo_change");
            echo $this->indexPartial();
            die;
            exit;
        }
    }
}
