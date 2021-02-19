<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->options('(:any)', '', ['filter' => 'cors']);
