<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Routes extends BaseController
{
    // This controller contains route functions.
    // Some functions available to anyone and others only available to club members logged into the joomla website.

    private const NO_MENU = false;

    /**
     * Constructor.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param LoggerInterface   $logger
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->currentMemberID = session()->userID;

        $this->routeModel = model('routeModel');
    }

    public function index()
    {
        return $this->routeList();
    }

    // Function to display list of routes
    // TODO allow for filter
    public function routeList()
    {
        $routes = $this->routeModel->getAllRoutes(null);
        $canEditAny = count(session()->roles) > 0;
        $canEditOwn = ($this->currentMemberID !== 0);
        return $this->loadPage('routeListView','Route archive list',
            array('routes' => $routes,
                  'css' => "routeList.css",
                  'userID' => $this->currentMemberID,
                  'canEditAny' => $canEditAny,
                  'canEditOwn' => $canEditOwn),
            self::NO_MENU
        );
    }

    public function downloadGpx($id)
    {
        $route = $this->routeModel->getRoute($id);
        $data = array('gpxfilename' => $route->gpxfilename, 'gpx' => $route->gpx);
        return view('downloadGpx', $data);
    }

    public function showRouteMapping($ids, $title)
    {
        // Leave ids as string for target to split up
        $data = array('routeIds' => $ids, 'css' => "routeList.css");
        return $this->loadPage('routeMapping', $title, $data, self::NO_MENU);
    }

}
