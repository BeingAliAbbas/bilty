<?php

require_once 'Controller.php';

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Bilty Management System',
            'pageTitle' => '🚚 Bilty Management System'
        ];

        echo $this->view('home/index', $data);
    }
}