<?php

namespace ToolsEd;

/**
 * CircleController Class
 *
 * Controller class for /circles/* URLs.  Handles circle-related activities, including listing circles, CRUD for circles, etc.
 *
 * @package ToolsEd
 * @author Samurdhi Karunarathne
 * @link http://www.toolsed.com/navigating/#structure
 */
class CircleController extends \ToolsEd\BaseController {

    /**
     * Create a new CircleController object.
     *
     * @param ToolsEd $app The main ToolsEd app.
     */
    public function __construct($app){
        $this->_app = $app;
    }

    /**
     * Renders the circle listing page.
     *
     * This page renders a table of user circles, with dropdown menus for modifying those circles.
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     * @todo implement interface to modify authorization hooks and permissions
     */
    public function pageCircle($id, $visitor_id){

        

        $circle_info = Circle::where('id',$id)->first();
        $circle_user = User::where('id',$visitor_id)->first();

        $circle = Circle::find($id);
        $circleUserList = $circle->users;


        /**
         * type means the type of visitor who's viewing this circle
         *
         * 0 - Guest(Not a memeber of the circle) 
         * 1 - Member but not admin
         * 2 - Admin 
         * By default this is 0(Guest)
         */
        $type = '0';

        error_log("Size ".sizeof($circleUserList));

        foreach ($circleUserList as $temp_user) {

            error_log($temp_user->pivot->id);
            
            if($temp_user->id == $visitor_id) {
                if($temp_user->pivot->admin == '1') $type = '2';
                else $type = '1';
            }
            
        }


        if(isset($circle_user['id'])){
            //$this->_app->render('circle.twig', ['circle'=>$circle_info,'visitor'=>$circle_user, 'users'=>$circleUserList, 'projects'=>$circleProjectList], 'type'=>$type); 
            $this->_app->render('circle.twig', ['circle'=>$circle_info,'visitor'=>$circle_user, 'type'=>$type]); 
            return true;
        }   
        return false;
    }

    public function pageCircleAuthorization($circle_id) {
        // Access-controlled page
        if (!$this->_app->user->checkAccess('uri_authorization_settings')){
            $this->_app->notFound();
        }

        $circle = Circle::find($circle_id);

        // Load all auth rules
        $rules = CircleAuth::where('circle_id', $circle_id)->get();

        $this->_app->render('config/authorization.twig', [
            "circle" => $circle,
            "rules" => $rules
        ]);

    }

    /**
     * Renders the form for creating a new circle.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the form, which can be embedded in other pages.
     * The form can be rendered in "modal" (for popup) or "panel" mode, depending on the value of the GET parameter `render`
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     */
    public function formCircleCreate(){
        // Access-controlled resource
        if (!$this->_app->user->checkAccess('create_circle')){
            $this->_app->notFound();
        }

        $get = $this->_app->request->get();

        if (isset($get['render']))
            $render = $get['render'];
        else
            $render = "modal";

        // Get a list of all themes
        $theme_list = $this->_app->site->getThemes();

        // Set default values
        $data['is_default'] = "0";
        // Set default title for new users
        $data['new_user_title'] = "New User";
        // Set default theme
        $data['theme'] = "default";
        // Set default icon
        $data['icon'] = "fa fa-user";
        // Set default landing page
        $data['landing_page'] = "dashboard";

        // Create a dummy Circle to prepopulate fields
        $circle = new Circle($data);

        if ($render == "modal")
            $template = "components/common/circle-info-modal.twig";
        else
            $template = "components/common/circle-info-panel.twig";

        // Determine authorized fields
        $fields = ['name', 'new_user_title', 'landing_page', 'theme', 'is_default', 'icon'];
        $show_fields = [];
        $disabled_fields = [];
        foreach ($fields as $field){
            if ($this->_app->user->checkAccess("update_circle_setting", ["property" => $field]))
                $show_fields[] = $field;
            else
                $disabled_fields[] = $field;
        }

        // Load validator rules
        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/circle-create.json");
        $this->_app->jsValidator->setSchema($schema);

        $this->_app->render($template, [
            "box_id" => $get['box_id'],
            "box_title" => "New Circle",
            "submit_button" => "Create circle",
            "form_action" => $this->_app->site->uri['public'] . "/circles",
            "circle" => $circle,
            "themes" => $theme_list,
            "fields" => [
                "disabled" => $disabled_fields,
                "hidden" => []
            ],
            "buttons" => [
                "hidden" => [
                    "edit", "delete"
                ]
            ],
            "validators" => $this->_app->jsValidator->rules()
        ]);
    }

    /**
     * Renders the form for editing an existing circle.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the form, which can be embedded in other pages.
     * The form can be rendered in "modal" (for popup) or "panel" mode, depending on the value of the GET parameter `render`.
     * Any fields that the user does not have permission to modify will be automatically disabled.
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     * @param int $circle_id the id of the circle to edit.
     */
    public function formCircleEdit($circle_id){
        // Access-controlled resource
        if (!$this->_app->user->checkAccess('uri_circles')){
            $this->_app->notFound();
        }

        $get = $this->_app->request->get();

        if (isset($get['render']))
            $render = $get['render'];
        else
            $render = "modal";

        // Get the circle to edit
        $circle = Circle::find($circle_id);

        // Get a list of all themes
        $theme_list = $this->_app->site->getThemes();

        if ($render == "modal")
            $template = "components/common/circle-info-modal.twig";
        else
            $template = "components/common/circle-info-panel.twig";

        // Determine authorized fields
        $fields = ['name', 'new_user_title', 'landing_page', 'theme', 'is_default'];
        $show_fields = [];
        $disabled_fields = [];
        $hidden_fields = [];
        foreach ($fields as $field){
            if ($this->_app->user->checkAccess("update_circle_setting", ["property" => $field]))
                $show_fields[] = $field;
            else if ($this->_app->user->checkAccess("view_circle_setting", ["property" => $field]))
                $disabled_fields[] = $field;
            else
                $hidden_fields[] = $field;
        }

        // Load validator rules
        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/circle-update.json");
        $this->_app->jsValidator->setSchema($schema);

        $this->_app->render($template, [
            "box_id" => $get['box_id'],
            "box_title" => "Edit Circle",
            "submit_button" => "Update circle",
            "form_action" => $this->_app->site->uri['public'] . "/circles/g/$circle_id",
            "circle" => $circle,
            "themes" => $theme_list,
            "fields" => [
                "disabled" => $disabled_fields,
                "hidden" => $hidden_fields
            ],
            "buttons" => [
                "hidden" => [
                    "edit", "delete"
                ]
            ],
            "validators" => $this->_app->jsValidator->rules()
        ]);
    }

    /**
     * Processes the request to create a new circle.
     *
     * Processes the request from the circle creation form, checking that:
     * 1. The circle name is not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @see formCircleCreate
     */
    public function createCircle($creatorId){
        $data = $this->_app->request->post();

        $initial_members=$data["circleMemberSelect"];

        var_dump($initial_members);

        unset($data["circleMemberSelect"]);




        // Perform desired data transformations on required fields.
        $data['name'] = trim($data['name']);

        // Create the circle
        $circle = new Circle($data);

        // Store new circle to database
        $circle->store();

        $creatorAdmin=User::find($creatorId);
        $creatorAdmin->circles()->attach($circle->id,['admin' => 1, 'creator'=> 1]);

        foreach ($initial_members as $initial_member_id){
            $initial_member=User::find($initial_member_id);
            $initial_member->circles()->attach($circle->id,['admin' => 0, 'creator'=> 0]);
        }


    }


    public function getPublicCircleList(){

        $circles=Circle::all();

        $ret=array();

        foreach($circles as $circle){
            $ret[$circle->id]=array("label"=>($circle->name),"photo"=>($circle->photo));
        }

        return $ret;

    }


    public function getCircleUserList($circle_id){


        $users=Circle::find($circle_id)->users;

        /*
        $ret=array();

        foreach($users as $user){
            error_log($user->id);
            $ret[$user->id]=array("label"=>($circle->name),"photo"=>($circle->photo));
        }

        */
        

        return $ret;

    }

    public function getCircleProjectList($circle_id){


        $projects=Circle::find($circle_id)->projects;

        /*

        $ret=array();

        foreach($projects as $project){
            error_log($project->id);
            $ret[$project->id]=array("label"=>($project->name),"photo"=>($project->photo));
        }

        */        
        

        return $ret;

    }


    /**
     * Processes the request to update an existing circle's details.
     *
     * Processes the request from the circle update form, checking that:
     * 1. The circle name is not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @param int $circle_id the id of the circle to edit.
     * @see formCircleEdit
     */
    public function updateCircle($circle_id){
        $post = $this->_app->request->post();

        // DEBUG: view posted data
        //error_log(print_r($post, true));

        // Load the request schema
        $requestSchema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/circle-update.json");

        // Get the alert message stream
        $ms = $this->_app->alerts;

        // Get the target circle
        $circle = Circle::find($circle_id);

        // If desired, put route-level authorization check here

        // Remove csrf_token
        unset($post['csrf_token']);

        // Check authorization for submitted fields, if the value has been changed
        foreach ($post as $name => $value) {
            if (isset($circle->$name) && $post[$name] != $circle->$name){
                // Check authorization
                if (!$this->_app->user->checkAccess('update_circle_setting', ['circle' => $circle, 'property' => $name])){
                    $ms->addMessageTranslated("danger", "ACCESS_DENIED");
                    $this->_app->halt(403);
                }
            } else if (!isset($circle->$name)) {
                $ms->addMessageTranslated("danger", "NO_DATA");
                $this->_app->halt(400);
            }
        }

        // Check that name is not already in use
        if (isset($post['name']) && $post['name'] != $circle->name && Circle::where('name', $post['name'])->first()){
            $ms->addMessageTranslated("danger", "GROUP_NAME_IN_USE", $post);
            $this->_app->halt(400);
        }

        // TODO: validate landing page route, theme, icon?

        // Set up Fortress to process the request
        $rf = new \Fortress\HTTPRequestFortress($ms, $requestSchema, $post);

        // Sanitize
        $rf->sanitize();

        // Validate, and halt on validation errors.
        if (!$rf->validate()) {
            $this->_app->halt(400);
        }

        // Get the filtered data
        $data = $rf->data();

        // Update the circle and generate success messages
        foreach ($data as $name => $value){
            if ($value != $circle->$name){
                $circle->$name = $value;
                // Add any custom success messages here
            }
        }

        $ms->addMessageTranslated("success", "GROUP_UPDATE", ["name" => $circle->name]);
        $circle->store();

    }

    /**
     * Processes the request to delete an existing circle.
     *
     * Deletes the specified circle, removing associations with any users and any circle-specific authorization rules.
     * Before doing so, checks that:
     * 1. The circle is deleteable (as specified in the `can_delete` column in the database);
     * 2. The circle is not currently set as the default primary circle;
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @param int $circle_id the id of the circle to delete.
     */
    public function deleteCircle($circle_id){
        $post = $this->_app->request->post();

        // Get the target circle
        $circle = Circle::find($circle_id);

        // Get the alert message stream
        $ms = $this->_app->alerts;

        // Check authorization
        if (!$this->_app->user->checkAccess('delete_circle', ['circle' => $circle])){
            $ms->addMessageTranslated("danger", "ACCESS_DENIED");
            $this->_app->halt(403);
        }

        // Check that we are allowed to delete this circle
        if ($circle->can_delete == "0"){
            $ms->addMessageTranslated("danger", "CANNOT_DELETE_GROUP", ["name" => $circle->name]);
            $this->_app->halt(403);
        }

        // Do not allow deletion if this circle is currently set as the default primary circle
        if ($circle->is_default == GROUP_DEFAULT_PRIMARY){
            $ms->addMessageTranslated("danger", "GROUP_CANNOT_DELETE_DEFAULT_PRIMARY", ["name" => $circle->name]);
            $this->_app->halt(403);
        }

        $ms->addMessageTranslated("success", "GROUP_DELETION_SUCCESSFUL", ["name" => $circle->name]);
        $circle->delete();       // TODO: implement Circle function
        unset($circle);
    }

}
