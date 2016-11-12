<?php

namespace ToolsEd;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Project Class
 *
 * Represents a Project object as stored in the database.
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
class Project extends TEModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "project";    

    public function __construct($properties = []) {
        parent::__construct($properties);
    }
    
    /**
     * Lazily load a collection of Users which belong to this project.
     */ 
    public function users(){
        $link_table = Database::getSchemaTable('project_user')->name;
        return $this->belongsToMany('ToolsEd\User', $link_table);
    }

    public function circle(){
        return $this->belongsTo('ToolsEd\Circle');
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
