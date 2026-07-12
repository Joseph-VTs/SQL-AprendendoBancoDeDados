<?php
// config/routes.php
return [
    '/' => 'catalog/index',
    '/login' => 'auth/login',
    '/auth/authenticate' => 'auth/authenticate',
    '/admin/cadastrar-empresa' => 'company/cadastrarEmpresa',
    '/admin/listar-empresas' => 'company/listarEmpresas',
    '/catalog/index' => 'catalog/index',
    '/company' => 'product/dashboard',
    '/company/' => 'product/dashboard',
    '/company/dashboard' => 'product/dashboard',
    '/company/cadastrar-produto' => 'product/create',
    '/company/salvar-produto' => 'product/store',
    '/company/store' => 'company/store',
    '/product/delete' => 'product/delete',
    '/logout' => 'auth/logout'
];
