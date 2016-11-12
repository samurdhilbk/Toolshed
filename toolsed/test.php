<?php
require_once 'vendor/autoload.php';
require_once 'models/auth/password.php';



$table_user_bio = new \ToolsEd\DatabaseTable("uf_user_bio", [
    "id",
    "user_id",
    "photo",
    "curr_occupation",
    "education",
    "interests"
]);

\ToolsEd\Database::setSchemaTable("uf_user_bio", $table_user_bio);

