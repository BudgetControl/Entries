<?php
namespace Budgetcontrol\Entry\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Controller {

    abstract protected function validate(Request|array $request);
    
}