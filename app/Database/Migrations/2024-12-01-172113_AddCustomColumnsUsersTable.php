<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomColumnsUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn("users", [
            "name" => [
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => false
            ],
            "role" => [
                "type" => "ENUM",
                'constraint' => ['ADMIN', 'FORGING', 'HEATING', 'FINISH', 'RM'],
                "null" => false
            ],
            "emp_id" => [
                "type" => "VARCHAR",
                "null" => false
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn("users", ["name", "role", "emp_id"]);
    }
}
