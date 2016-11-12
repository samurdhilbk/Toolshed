<?php

namespace ToolsEd;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * UserBio Class
 *
 * Represents a UserBio object as stored in the database.
 *
 * @package ToolsEd
 * @author Samurdhi Karunarathne
 *
 * @property int id
 * @property int user_id
 * @property BLOB photo
 * @property string curr_occupation
 * @property string education
 * @property string interests
 */
class UserBio extends TEModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "user_bio";    


    
}
