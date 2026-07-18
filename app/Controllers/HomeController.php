<?php

namespace Controllers;

use Core\Controller;

class HomeController extends Controller
{
    /** GET / — 平台入口首頁 */
    public function index(): void
    {
        $this->render('home.index', [
            'title' => '新零售行銷多元平台 - NewPay',
            'hideNavbar' => true,
        ]);
    }
}
