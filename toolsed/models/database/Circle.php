<?php

namespace ToolsEd;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Circle Class
 *
 * Represents a Circle object as stored in the database.
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
class Circle extends TEModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "circle";    

    public function __construct($properties = []) {
        parent::__construct($properties);
    }
    
    /**
     * Lazily load a collection of Users which belong to this circle.
     */ 
    public function users(){
        $link_table = Database::getSchemaTable('circle_user')->name;
        return $this->belongsToMany('ToolsEd\User', $link_table);
    }

    public function projects(){
        return $this->hasMany('ToolsEd\Project', "circle_id", "id");
    }
    
    public function save(array $options = []){

        return parent::save($options);
    }
    
    /**
     * Delete this group from the database, along with any linked user and authorization rules
     *
     */
    public function delete(){        
        // Remove all user associations
        $this->users()->detach();
        
            
        $result = parent::delete();        
        
        return $result;
    }


    
}
