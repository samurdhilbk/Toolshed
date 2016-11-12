<?php

namespace ToolsEd;

/**
 * ProjectController Class
 *
 * Controller class for /projects/* URLs.  Handles project-related activities, including listing projects, CRUD for projects, etc.
 *
 * @package ToolsEd
 * @author Samurdhi Karunarathne
 * @link http://www.toolsed.com/navigating/#structure
 */
class ProjectController extends \ToolsEd\BaseController {

    /**
     * Create a new ProjectController object.
     *
     * @param ToolsEd $app The main ToolsEd app.
     */
    public function __construct($app){
        $this->_app = $app;
    }

    /**
     * Renders the project listing page.
     *
     * This page renders a table of user projects, with dropdown menus for modifying those projects.
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     * @todo implement interface to modify authorization hooks and permissions
     */
    public function pageProjects(){
        // Access-controlled page
        if (!$this->_app->user->checkAccess('uri_projects')){
            $this->_app->notFound();
        }

        $projects = Project::queryBuilder()->get();

        $this->_app->render('projects/projects.twig', [
            "projects" => $projects
        ]);
    }

    public function pageProjectAuthorization($project_id) {
        // Access-controlled page
        if (!$this->_app->user->checkAccess('uri_authorization_settings')){
            $this->_app->notFound();
        }

        $project = Project::find($project_id);

        // Load all auth rules
        $rules = ProjectAuth::where('project_id', $project_id)->get();

        $this->_app->render('config/authorization.twig', [
            "project" => $project,
            "rules" => $rules
        ]);

    }

    public function getPublicProjectList(){

        $projects=Project::all();

        $ret=array();

        foreach($projects as $project){
            $ret[$project->id]=array("label"=>($project->name),"photo"=>($project->photo));
        }

        return $ret;

    }

    /**
     * Renders the form for creating a new project.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the form, which can be embedded in other pages.
     * The form can be rendered in "modal" (for popup) or "panel" mode, depending on the value of the GET parameter `render`
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     */
    public function formProjectCreate(){
        // Access-controlled resource
        if (!$this->_app->user->checkAccess('create_project')){
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

        // Create a dummy Project to prepopulate fields
        $project = new Project($data);

        if ($render == "modal")
            $template = "components/common/project-info-modal.twig";
        else
            $template = "components/common/project-info-panel.twig";

        // Determine authorized fields
        $fields = ['name', 'new_user_title', 'landing_page', 'theme', 'is_default', 'icon'];
        $show_fields = [];
        $disabled_fields = [];
        foreach ($fields as $field){
            if ($this->_app->user->checkAccess("update_project_setting", ["property" => $field]))
                $show_fields[] = $field;
            else
                $disabled_fields[] = $field;
        }

        // Load validator rules
        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/project-create.json");
        $this->_app->jsValidator->setSchema($schema);

        $this->_app->render($template, [
            "box_id" => $get['box_id'],
            "box_title" => "New Project",
            "submit_button" => "Create project",
            "form_action" => $this->_app->site->uri['public'] . "/projects",
            "project" => $project,
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
     * Renders the form for editing an existing project.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the form, which can be embedded in other pages.
     * The form can be rendered in "modal" (for popup) or "panel" mode, depending on the value of the GET parameter `render`.
     * Any fields that the user does not have permission to modify will be automatically disabled.
     * This page requires authentication (and should generally be limited to admins or the root user).
     * Request type: GET
     * @param int $project_id the id of the project to edit.
     */
    public function formProjectEdit($project_id){
        // Access-controlled resource
        if (!$this->_app->user->checkAccess('uri_projects')){
            $this->_app->notFound();
        }

        $get = $this->_app->request->get();

        if (isset($get['render']))
            $render = $get['render'];
        else
            $render = "modal";

        // Get the project to edit
        $project = Project::find($project_id);

        // Get a list of all themes
        $theme_list = $this->_app->site->getThemes();

        if ($render == "modal")
            $template = "components/common/project-info-modal.twig";
        else
            $template = "components/common/project-info-panel.twig";

        // Determine authorized fields
        $fields = ['name', 'new_user_title', 'landing_page', 'theme', 'is_default'];
        $show_fields = [];
        $disabled_fields = [];
        $hidden_fields = [];
        foreach ($fields as $field){
            if ($this->_app->user->checkAccess("update_project_setting", ["property" => $field]))
                $show_fields[] = $field;
            else if ($this->_app->user->checkAccess("view_project_setting", ["property" => $field]))
                $disabled_fields[] = $field;
            else
                $hidden_fields[] = $field;
        }

        // Load validator rules
        $schema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/project-update.json");
        $this->_app->jsValidator->setSchema($schema);

        $this->_app->render($template, [
            "box_id" => $get['box_id'],
            "box_title" => "Edit Project",
            "submit_button" => "Update project",
            "form_action" => $this->_app->site->uri['public'] . "/projects/g/$project_id",
            "project" => $project,
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
     * Processes the request to create a new project.
     *
     * Processes the request from the project creation form, checking that:
     * 1. The project name is not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @see formProjectCreate
     */
    public function createProject($creatorId){
        $data = $this->_app->request->post();

        

        //var_dump($initial_members);
        $initial_members=array();

        $isMultiple=false;
        $isCircleProject=false;

        if(isset($data["optionalMemberSelect"])){
            $isMultiple=true;
            $initial_members=$data["optionalMemberSelect"];
            unset($data["optionalMemberSelect"]);
        }

        if(isset($data["circle_id"])){
            $isCircleProject=true;
        }
        else{
            $data["circle_id"]=0;
        }

        if(isset($data["hasDeadline"])) $data["hasDeadline"]='1';
        else $data["hasDeadline"]='0';
    

        // Perform desired data transformations on required fields.
        $data['name'] = trim($data['name']);

        // Create the project
        $project = new Project($data);

        // Store new project to database
        $project->store();

        $creatorAdmin=User::find($creatorId);
        $creatorAdmin->projects()->attach($project->id,['admin' => 1, 'creator'=> 1]);

        if($isMultiple){        
            foreach ($initial_members as $initial_member_id){
                $initial_member=User::find($initial_member_id);
                $initial_member->projects()->attach($project->id,['admin' => 0, 'creator'=> 0]);
            }
        }



    }

    /**
     * Processes the request to update an existing project's details.
     *
     * Processes the request from the project update form, checking that:
     * 1. The project name is not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @param int $project_id the id of the project to edit.
     * @see formProjectEdit
     */
    public function updateProject($project_id){
        $post = $this->_app->request->post();

        // DEBUG: view posted data
        //error_log(print_r($post, true));

        // Load the request schema
        $requestSchema = new \Fortress\RequestSchema($this->_app->config('schema.path') . "/forms/project-update.json");

        // Get the alert message stream
        $ms = $this->_app->alerts;

        // Get the target project
        $project = Project::find($project_id);

        // If desired, put route-level authorization check here

        // Remove csrf_token
        unset($post['csrf_token']);

        // Check authorization for submitted fields, if the value has been changed
        foreach ($post as $name => $value) {
            if (isset($project->$name) && $post[$name] != $project->$name){
                // Check authorization
                if (!$this->_app->user->checkAccess('update_project_setting', ['project' => $project, 'property' => $name])){
                    $ms->addMessageTranslated("danger", "ACCESS_DENIED");
                    $this->_app->halt(403);
                }
            } else if (!isset($project->$name)) {
                $ms->addMessageTranslated("danger", "NO_DATA");
                $this->_app->halt(400);
            }
        }

        // Check that name is not already in use
        if (isset($post['name']) && $post['name'] != $project->name && Project::where('name', $post['name'])->first()){
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

        // Update the project and generate success messages
        foreach ($data as $name => $value){
            if ($value != $project->$name){
                $project->$name = $value;
                // Add any custom success messages here
            }
        }

        $ms->addMessageTranslated("success", "GROUP_UPDATE", ["name" => $project->name]);
        $project->store();

    }

    /**
     * Processes the request to delete an existing project.
     *
     * Deletes the specified project, removing associations with any users and any project-specific authorization rules.
     * Before doing so, checks that:
     * 1. The project is deleteable (as specified in the `can_delete` column in the database);
     * 2. The project is not currently set as the default primary project;
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @param int $project_id the id of the project to delete.
     */
    public function deleteProject($project_id){
        $post = $this->_app->request->post();

        // Get the target project
        $project = Project::find($project_id);

        // Get the alert message stream
        $ms = $this->_app->alerts;

        // Check authorization
        if (!$this->_app->user->checkAccess('delete_project', ['project' => $project])){
            $ms->addMessageTranslated("danger", "ACCESS_DENIED");
            $this->_app->halt(403);
        }

        // Check that we are allowed to delete this project
        if ($project->can_delete == "0"){
            $ms->addMessageTranslated("danger", "CANNOT_DELETE_GROUP", ["name" => $project->name]);
            $this->_app->halt(403);
        }

        // Do not allow deletion if this project is currently set as the default primary project
        if ($project->is_default == GROUP_DEFAULT_PRIMARY){
            $ms->addMessageTranslated("danger", "GROUP_CANNOT_DELETE_DEFAULT_PRIMARY", ["name" => $project->name]);
            $this->_app->halt(403);
        }

        $ms->addMessageTranslated("success", "GROUP_DELETION_SUCCESSFUL", ["name" => $project->name]);
        $project->delete();       // TODO: implement Project function
        unset($project);
    }

}
