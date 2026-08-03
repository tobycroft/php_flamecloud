<?php
declare (strict_types = 1);

use app\BaseController;

class CommonController extends BaseController
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->setCors();
    }

    protected function setCors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        if ($this->request->method() === 'OPTIONS') {
            exit(200);
        }
    }
}