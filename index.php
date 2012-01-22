<?php
    require_once realpath(dirname(__FILE__) . '/slim/Slim/Slim.php');

    spl_autoload_register("neo_autoload");

    require_once realpath(dirname(__FILE__)."/slim-plugins/Plugins/autoroute/AutoRoutePlugin.php");
    require_once realpath(dirname(__FILE__)."/slim-plugins/Plugins/acl/AccessControlPlugin.php");

    require_once realpath(dirname(__FILE__)."/services/CallCentreManager.php");

    $routes = array();

    $app = new Slim();
    $app->config('debug', false);

    $app->add('Slim_Middleware_ContentTypes');

    $app->registerPlugin("AccessControlPlugin");
    $app->registerPlugin("AutoRoutePlugin", array(new CallCentreManager()));

    AccessControlPlugin::authorizationCallback(function($users)
    {
        return in_array("admin", $users);
    });

    $app->get("/", function()
    {
        return "Hello!";
    });

    $app->customRouter(function($callable, $route, $params) use ($routes) {

        $app = Slim::getInstance();

        $data = null;

        if (in_array("PUT", $route->getHttpMethods()) || in_array("POST", $route->getHttpMethods()))
            $data = array($app->request()->getBody());
        else
            $data = $params;

        if(!is_callable($callable))
            return false;

        $result = call_user_func_array($callable, $data);

        if(empty($result))
            return true;

        $app->contentType("application/json");
        $app->response()->header("Access-Control-Allow-Origin", "*");

        echo json_encode($result);
        return true;
    });

    // Custom error handler, only used when debugging is turned OFF
    $app->error(function(Exception $e)
    {
        $app = Slim::getInstance();

        $app->contentType("application/json");
        $app->response()->header("Access-Control-Allow-Origin", "*");

        $app->halt(501, json_encode(array(
                                         "error" => array(
                                             "message" => $e->getMessage(),
                                             "code"    => $e->getCode()
                                         )
                                    )));
    });

    // Custom 404 handler
    $app->notFound(function()
    {
        $app = Slim::getInstance();

        $app->contentType("application/json");
        $app->response()->header("Access-Control-Allow-Origin", "*");

        $app->halt(500, json_encode(array(
                                         "error" => array(
                                             "message" => "'" . $app->request()->getResourceUri() . "' could not be resolved to a valid API call",
                                             "code"    => 0
                                         )
                                    )));
    });

    /**
     * Autoload the neo4jphp lib files
     * @param $file
     */
    function neo_autoload($file)
    {
        $base = dirname(__FILE__) . "/neo4jphp/lib";
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $file) . '.php';
        if (file_exists($base . DIRECTORY_SEPARATOR . $filename))
            require_once($base . DIRECTORY_SEPARATOR . $filename);
    }

    // run Slim!
    $app->run();
?>