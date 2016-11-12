<?php
    // This is the path to initialize.php, your site's gateway to the rest of the TE codebase!  Make sure that it is correct!
    $init_path = "../toolsed/initialize.php";

    // This if-block just checks that the path for initialize.php is correct.  Remove this once you know what you're doing.
    if (!file_exists($init_path)){
        echo "<h2>We can't seem to find our way to initialize.php!  Please check the require_once statement at the top of index.php, and make sure it contains the correct path to initialize.php.</h2><br>";
    }

    require_once($init_path);

    use ToolsEd as TE;
   
    // Front page
    $app->get('/', function () use ($app) {
        // This if-block detects if mod_rewrite is enabled.
        // This is just an anti-noob device, remove it if you know how to read the docs and/or breathe through your nose.
        if (isset($_SERVER['SERVER_TYPE']) && ($_SERVER['SERVER_TYPE'] == "Apache") && !isset($_SERVER['HTTP_MOD_REWRITE'])) {
            $app->render('errors/bad-config.twig');
            exit;
        }
    
        // Check that we can connect to the DB.  Again, you can remove this if you know what you're doing.
        if (!TE\Database::testConnection()){
            // In case the error is because someone is trying to reinstall with new db info while still logged in, log them out
            session_destroy();
            // TODO: log out from remember me as well.
            $controller = new TE\AccountController($app);
            return $controller->pageDatabaseError();
        }
        
        // Forward to installation if not complete
        // TODO: Is there any way to detect that installation was complete, but the DB is malfunctioning?
        if (!isset($app->site->install_status) || $app->site->install_status == "pending"){
            $app->redirect($app->urlFor('uri_install'));
        }
        
        // Forward to the user's landing page (if logged in), otherwise take them to the home page
        // This is probably where you, the developer, would start making changes if you need to change the default behavior.
        if ($app->user->isGuest()){
            $controller = new TE\AccountController($app);
            $controller->pageHome();
        // If this is the first the root user is logging in, take them to site settings
        } else if ($app->user->id == $app->config('user_id_master') && $app->site->install_status == "new"){
            $app->site->install_status = "complete";
            $app->site->store();
            $app->alerts->addMessage("success", "Congratulations, you've successfully logged in for the first time.  Please take a moment to customize your site settings.");
            $app->redirect($app->urlFor('uri_settings'));  
        } else {
            $app->redirect($app->user->landing_page);        
        }
        
    })->name('uri_home');

    /********** FEATURE PAGES **********/
    
    $app->get('/dashboard/?', function () use ($app) {    
        // Access-controlled page
        if (!$app->user->checkAccess('uri_dashboard')){
            $app->notFound();
        }
        
        $app->render('dashboard.twig', []);          
    });

    $app->get('/bio/?', function () use ($app) {    
        // Access-controlled page
        if (!$app->user->checkAccess('uri_dashboard')){
            $app->notFound();
        }

        $get = $app->request->get();
        $controller = new TE\UserController($app);

        if($get['action']=='display'){
            
            $ret=$controller->pageUserBio($get['id']);

            if(!$ret) $app->notFound();
        }

        else if($get['action']=='update'){

            $controller->updateUserBio($get['id'],$get['column'],$get['value']);
            $ret=$controller->getUserBio($get['id'],$get['column']);
            error_log($ret);
            echo json_encode(array('newval' => $ret));
        }
        
              
    });

    $app->post('/bio/?', function() use ($app) {    

        if (!$app->user->checkAccess('uri_dashboard')){
            $app->notFound();
        }

        if($_GET['action']=='update'){

            if(isset($_FILES['file'])){

                $file = array('name'=>$_FILES['file']['name'],'size'=>$_FILES['file']['size'],'tmp_name'=>$_FILES['file']['tmp_name'],'type'=>$_FILES['file']['type']);
                //var_dump($_GET);
                //var_dump($_POST);
                
                $id=$_GET['id'];
                $loc='C:/xampp/htdocs/home/public/images/profile/'.$id;
                //error_log($loc);
                $controller = new TE\UserController($app);
                $controller->updateUserPhoto($id, $file, $loc);
                $ret=$controller->getUserBio($id, 'photo');
                error_log($ret);
                $data=array('photo_loc' => $ret);
                echo $ret;
                
            }
        }
        
              
    });

    $app->get('/getPublicUserList/?', function() use ($app) {    

        $controller = new TE\UserController($app);
        $ret=$controller->getPublicUserList();
        echo json_encode($ret);      
    });

    
    $app->get('/getPublicCircleList/?', function() use ($app) {    
        
        $controller = new TE\CircleController($app);
        $ret=$controller->getPublicCircleList();
        echo json_encode($ret);      
    });

    $app->get('/getUserCircleList/?', function() use ($app) {    
        
        $controller = new TE\UserController($app);
        $ret=$controller->getUserCircleList($_GET['id']);

        echo json_encode($ret);      
    });

    $app->get('/getUserProjectList/?', function() use ($app) {    
        
        $controller = new TE\UserController($app);
        $ret=$controller->getUserProjectList($_GET['id']);

        echo json_encode($ret);      
    });

    
    $app->get('/getPublicProjectList/?', function() use ($app) {    
        
        //error_log("Hereeee");
        $controller = new TE\ProjectController($app);
        $ret=$controller->getPublicProjectList();
        echo json_encode($ret);      
    });

    


    /*
    $app->post('/bio/?', 'upload');


    function upload(){

        $id=$_POST['id'];
        $loc=$_POST['loc'];

        $file = $_FILES['image'];
        $controller = new TE\UserController($app);
        $controller->updateUserPhoto($id, $file, $loc);
        $ret=$controller->getUserBio($id, 'photo');
        error_log($ret);
        echo json_encode(array('photo_loc' => $ret));
    }
    */

    $app->get('/zerg/?', function () use ($app) {    
        // Access-controlled page
        if (!$app->user->checkAccess('uri_zerg')){
            $app->notFound();
        }
        
        $app->render('users/zerg.twig'); 
    });    
       
    /********** ACCOUNT MANAGEMENT INTERFACE **********/
    
    $app->get('/account/:action/?', function ($action) use ($app) {    
        // Forward to installation if not complete
        if (!isset($app->site->install_status) || $app->site->install_status == "pending"){
            $app->redirect($app->urlFor('uri_install'));
        }
    
        $get = $app->request->get();
        
        $controller = new TE\AccountController($app);
    
        $twig = $app->view()->getEnvironment();   
        $loader = $twig->getLoader();
          
        switch ($action) {
            case "login":               return $controller->pageLogin();
            case "logout":              return $controller->logout(true); 
            case "register":            return $controller->pageRegister();         
            case "resend-activation":   return $controller->pageResendActivation();
            case "forgot-password":     return $controller->pageForgotPassword();
            case "activate":            return $controller->activate();
            case "set-password":        return $controller->pageSetPassword(true); 
            case "reset-password":      if (isset($get['confirm']) && $get['confirm'] == "true")
                                            return $controller->pageSetPassword(false);
                                        else
                                            return $controller->denyResetPassword();
            case "captcha":             return $controller->captcha();
            case "settings":            return $controller->pageAccountSettings();
            default:                    return $controller->page404();   
        }
    });

    $app->post('/account/:action/?', function ($action) use ($app) {            
        $controller = new TE\AccountController($app);
    
        switch ($action) {
            case "login":               return $controller->login();     
            case "register":            return $controller->register();
            case "resend-activation":   return $controller->resendActivation();
            case "forgot-password":     return $controller->forgotPassword();
            case "set-password":        return $controller->setPassword(true);
            case "reset-password":      return $controller->setPassword(false);            
            case "settings":            return $controller->accountSettings();
            default:                    $app->notFound();
        }
    });    
    
    /********** USER MANAGEMENT INTERFACE **********/
    
    // List users
    $app->get('/users/?', function () use ($app) {
        $controller = new TE\UserController($app);
        return $controller->pageUsers();
    })->name('uri_users');    

    // List users in a particular primary group
    $app->get('/users/:primary_group/?', function ($primary_group) use ($app) {
        $controller = new TE\UserController($app);
        return $controller->pageUsers($primary_group);
    });
    
    // User info form (update/view)
    $app->get('/forms/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new TE\UserController($app);
        $get = $app->request->get();        
        if (isset($get['mode']) && $get['mode'] == "update")
            return $controller->formUserEdit($user_id);
        else
            return $controller->formUserView($user_id);
    });  

    // User edit password form
    $app->get('/forms/users/u/:user_id/password/?', function ($user_id) use ($app) {
        $controller = new TE\UserController($app);
        $get = $app->request->get();        
        return $controller->formUserEditPassword($user_id);
    });
    
    // User creation form
    $app->get('/forms/users/?', function () use ($app) {
        $controller = new TE\UserController($app);
        return $controller->formUserCreate();
    });
    
    // User info page
    $app->get('/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new TE\UserController($app);
        return $controller->pageUser($user_id);
    });       

    // Create user
    $app->post('/users/?', function () use ($app) {
        $controller = new TE\UserController($app);
        return $controller->createUser();
    });
    
    // Update user info
    $app->post('/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new TE\UserController($app);
        return $controller->updateUser($user_id);
    });       
    
    // Delete user
    $app->post('/users/u/:user_id/delete/?', function ($user_id) use ($app) {
        $controller = new TE\UserController($app);
        return $controller->deleteUser($user_id);
    });
    
    /********** GROUP MANAGEMENT INTERFACE **********/
    
    // List groups
    $app->get('/groups/?', function () use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->pageGroups();
    }); 
    
    // List auth rules for a group
    $app->get('/groups/g/:group_id/auth?', function ($group_id) use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->pageGroupAuthorization($group_id);
    })->name('uri_authorization');  
    
    // Group info form (update/view)
    $app->get('/forms/groups/g/:group_id/?', function ($group_id) use ($app) {
        $controller = new TE\GroupController($app);
        $get = $app->request->get();        
        if (isset($get['mode']) && $get['mode'] == "update")
            return $controller->formGroupEdit($group_id);
        else
            return $controller->formGroupView($group_id);
    });

    // Group creation form
    $app->get('/forms/groups/?', function () use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->formGroupCreate();
    });    
    
    // Create group
    $app->post('/groups/?', function () use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->createGroup();
    });
    
    // Update group info
    $app->post('/groups/g/:group_id/?', function ($group_id) use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->updateGroup($group_id);
    });       

    // Delete group
    $app->post('/groups/g/:group_id/delete/?', function ($group_id) use ($app) {
        $controller = new TE\GroupController($app);
        return $controller->deleteGroup($group_id);
    });
    
    /********** GROUP AUTH RULES INTERFACE **********/
    
    // Group auth creation form
    $app->get('/forms/groups/g/:group_id/auth/?', function ($group_id) use ($app) {
        $controller = new TE\AuthController($app);
        return $controller->formAuthCreate($group_id, "group");
    });      
    
    // Group auth update form
    $app->get('/forms/groups/auth/a/:rule_id/?', function ($rule_id) use ($app) {
        $controller = new TE\AuthController($app);
        $get = $app->request->get();        
        return $controller->formAuthEdit($rule_id);
    });    

    // Group auth create
    $app->post('/groups/g/:group_id/auth/?', function ($group_id) use ($app) {
        $controller = new TE\AuthController($app);
        return $controller->createAuthRule($group_id, "group");
    });     

    // Group auth update
    $app->post('/groups/auth/a/:rule_id?', function ($rule_id) use ($app) {
        $controller = new TE\AuthController($app);
        return $controller->updateAuthRule($rule_id, "group");
    });
    
    // Group auth delete
    $app->post('/auth/a/:rule_id/delete/?', function ($rule_id) use ($app) {
        $controller = new TE\AuthController($app);
        $get = $app->request->get();        
        return $controller->deleteAuthRule($rule_id);
    }); 


    /*********** CIRCLE MANAGEMENT INTERFACE *********************************/

    $app->post('/circles/?', function () use ($app) {
        error_log("Here");
        $controller = new TE\CircleController($app);
        $controller->createCircle($app->user->id);
        $app->render('dashboard.twig', []); 
    });

    /*********** PROJECT MANAGEMENT INTERFACE ********************************/


    $app->post('/projects/?', function () use ($app) {
        //error_log("Here");
        $controller = new TE\ProjectController($app);
        $controller->createProject($app->user->id);
        $app->render('dashboard.twig', []); 
    });


        
    /************ ADMIN TOOLS *************/
    
    $app->get('/config/settings/?', function () use ($app) {
        $controller = new TE\AdminController($app);
        return $controller->pageSiteSettings();
    })->name('uri_settings');     
    
    $app->post('/config/settings/?', function () use ($app) {
        $controller = new TE\AdminController($app);
        return $controller->siteSettings();        
    });
    
    // Build the minified, concatenated CSS and JS
    $app->get('/config/build', function() use ($app){
        // Access-controlled page
        if (!$app->user->checkAccess('uri_minify')){
            $app->notFound();
        }
        
        $app->schema->build(true);
        $app->alerts->addMessageTranslated("success", "MINIFICATION_SUCCESS");
        $app->redirect($app->urlFor('uri_settings'));
    });    
    
    // Slim info page
    $app->get('/sliminfo/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_slim_info')){
            $app->notFound();
        }
        echo "<pre>";
        print_r($app->environment());
        echo "</pre>";
    });

    // PHP info page
    $app->get('/phpinfo/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_php_info')){
            $app->notFound();
        }    
        echo "<pre>";
        print_r(phpinfo());
        echo "</pre>";
    });

    // Error log page
    $app->get('/errorlog/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_error_log')){
            $app->notFound();
        }
        $log = $app->site->getLog();
        echo "<pre>";
        echo implode("<br>",$log['messages']);
        echo "</pre>";
    });      
       
    /************ INSTALLER *************/

    $app->get('/install/?', function () use ($app) {
        $controller = new TE\InstallController($app);
        if (isset($app->site->install_status)){
            // If tables have been created, move on to master account setup
            if ($app->site->install_status == "pending"){
                $app->redirect($app->site->uri['public'] . "/install/master");
            } else {
                // Everything is set up, so go to the home page!
                $app->redirect($app->urlFor('uri_home'));
            }
        } else {
            return $controller->pageSetupDB();
        }
    })->name('uri_install');
    
    $app->get('/install/master/?', function () use ($app) {
        error_log("fdgfd");
        $controller = new TE\InstallController($app);
        if (isset($app->site->install_status) && ($app->site->install_status == "pending")){
            return $controller->pageSetupMasterAccount();
        } else {
            $app->redirect($app->urlFor('uri_install'));
        }
    });

    $app->post('/install/:action/?', function ($action) use ($app) {
        error_log("fdgfd");
        $controller = new TE\InstallController($app);
        switch ($action) {
            case "master":            return $controller->setupMasterAccount();      
            default:                  $app->notFound();
        }   
    });
    
    /************ API *************/
    
    $app->get('/api/users/?', function () use ($app) {
        $controller = new TE\ApiController($app);
        $controller->listUsers();
    });
    
    
    /************ MISCELLANEOUS UTILITY ROUTES *************/
    
    // Generic confirmation dialog
    $app->get('/forms/confirm/?', function () use ($app) {
        $get = $app->request->get();
        
        // Load the request schema
        $requestSchema = new \Fortress\RequestSchema($app->config('schema.path') . "/forms/confirm-modal.json");
        
        // Get the alert message stream
        $ms = $app->alerts;         
        
        // Remove csrf_token
        unset($get['csrf_token']);
        
        // Set up Fortress to process the request
        $rf = new \Fortress\HTTPRequestFortress($ms, $requestSchema, $get);                    
    
        // Sanitize
        $rf->sanitize();
    
        // Validate, and halt on validation errors.
        if (!$rf->validate()) {
            $app->halt(400);
        }           
        
        $data = $rf->data();
        
        $app->render('components/common/confirm-modal.twig', $data);   
    }); 
    
    // Alert stream
    $app->get('/alerts/?', function () use ($app) {
        $controller = new TE\BaseController($app);
        $controller->alerts();
    });
    
    // JS Config
    $app->get($app->config('uri')['js-relative'] . '/config.js', function () use ($app) {
        $controller = new TE\BaseController($app);
        $controller->configJS();
    });
    
    // Theme CSS
    $app->get($app->config('uri')['css-relative'] . '/theme.css', function () use ($app) {
        $controller = new TE\BaseController($app);
        $controller->themeCSS();
    });
    
    // Not found page (404)
    $app->notFound(function () use ($app) {
        if ($app->request->isGet()) {
            $controller = new TE\BaseController($app);
            $controller->page404();
        } else {
            $app->alerts->addMessageTranslated("danger", "SERVER_ERROR");
        }
    });
    
    $app->run();
