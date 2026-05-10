<?php

return [
    // Auth routes
    ['method' => 'POST', 'path' => '/api/auth/register', 'handler' => 'App\Controllers\AuthController@register'],
    ['method' => 'POST', 'path' => '/api/auth/login', 'handler' => 'App\Controllers\AuthController@login'],
    ['method' => 'POST', 'path' => '/api/auth/logout', 'handler' => 'App\Controllers\AuthController@logout', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/auth/refresh', 'handler' => 'App\Controllers\AuthController@refresh'],
    
    // User routes
    ['method' => 'GET', 'path' => '/api/user/profile', 'handler' => 'App\Controllers\UserController@profile', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/user/profile', 'handler' => 'App\Controllers\UserController@updateProfile', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/user/password', 'handler' => 'App\Controllers\UserController@updatePassword', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/user/avatar', 'handler' => 'App\Controllers\UserController@uploadAvatar', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/user/search', 'handler' => 'App\Controllers\UserController@search', 'middleware' => ['auth']],
    
    // Friend routes
    ['method' => 'GET', 'path' => '/api/friends', 'handler' => 'App\Controllers\FriendController@index', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/friends/pending', 'handler' => 'App\Controllers\FriendController@pending', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/friends/request', 'handler' => 'App\Controllers\FriendController@sendRequest', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/friends/request/{id}', 'handler' => 'App\Controllers\FriendController@handleRequest', 'middleware' => ['auth']],
    ['method' => 'DELETE', 'path' => '/api/friends/{friendId}', 'handler' => 'App\Controllers\FriendController@delete', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/friends/search', 'handler' => 'App\Controllers\FriendController@search', 'middleware' => ['auth']],
    
    // Private message routes
    ['method' => 'POST', 'path' => '/api/message/private', 'handler' => 'App\Controllers\MessageController@sendPrivate', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/message/private/history/{userId}', 'handler' => 'App\Controllers\MessageController@getHistory', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/message/private/read/{userId}', 'handler' => 'App\Controllers\MessageController@markRead', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/message/private/unread', 'handler' => 'App\Controllers\MessageController@getUnread', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/message/conversations', 'handler' => 'App\Controllers\MessageController@getConversations', 'middleware' => ['auth']],
    
    // Group routes
    ['method' => 'GET', 'path' => '/api/group', 'handler' => 'App\Controllers\GroupController@index', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/group', 'handler' => 'App\Controllers\GroupController@store', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/group/{id}', 'handler' => 'App\Controllers\GroupController@show', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/group/{id}/join', 'handler' => 'App\Controllers\GroupController@join', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/group/{id}/leave', 'handler' => 'App\Controllers\GroupController@leave', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/group/{id}/admin/{userId}', 'handler' => 'App\Controllers\GroupController@setAdmin', 'middleware' => ['auth']],
    ['method' => 'DELETE', 'path' => '/api/group/{id}/member/{userId}', 'handler' => 'App\Controllers\GroupController@removeMember', 'middleware' => ['auth']],
    ['method' => 'PUT', 'path' => '/api/group/{id}', 'handler' => 'App\Controllers\GroupController@update', 'middleware' => ['auth']],
    ['method' => 'DELETE', 'path' => '/api/group/{id}', 'handler' => 'App\Controllers\GroupController@disband', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/api/group/{id}/message', 'handler' => 'App\Controllers\GroupController@sendMessage', 'middleware' => ['auth']],
    ['method' => 'GET', 'path' => '/api/group/{id}/message', 'handler' => 'App\Controllers\GroupController@getMessages', 'middleware' => ['auth']],
    
    // Admin routes
    ['method' => 'POST', 'path' => '/api/admin/login', 'handler' => 'App\Controllers\AdminController@login'],
    ['method' => 'POST', 'path' => '/api/admin/logout', 'handler' => 'App\Controllers\AdminController@logout', 'middleware' => ['admin']],
    ['method' => 'GET', 'path' => '/api/admin/dashboard', 'handler' => 'App\Controllers\AdminController@dashboard', 'middleware' => ['admin']],
    
    // Admin user management
    ['method' => 'GET', 'path' => '/api/admin/users', 'handler' => 'App\Controllers\AdminController@users', 'middleware' => ['admin']],
    ['method' => 'GET', 'path' => '/api/admin/users/{id}', 'handler' => 'App\Controllers\AdminController@showUser', 'middleware' => ['admin']],
    ['method' => 'PUT', 'path' => '/api/admin/users/{id}', 'handler' => 'App\Controllers\AdminController@updateUser', 'middleware' => ['admin']],
    ['method' => 'DELETE', 'path' => '/api/admin/users/{id}', 'handler' => 'App\Controllers\AdminController@deleteUser', 'middleware' => ['admin']],
    ['method' => 'PUT', 'path' => '/api/admin/users/{id}/status', 'handler' => 'App\Controllers\AdminController@updateUserStatus', 'middleware' => ['admin']],
    
    // Admin group management
    ['method' => 'GET', 'path' => '/api/admin/groups', 'handler' => 'App\Controllers\AdminController@groups', 'middleware' => ['admin']],
    ['method' => 'GET', 'path' => '/api/admin/groups/{id}', 'handler' => 'App\Controllers\AdminController@showGroup', 'middleware' => ['admin']],
    ['method' => 'PUT', 'path' => '/api/admin/groups/{id}', 'handler' => 'App\Controllers\AdminController@updateGroup', 'middleware' => ['admin']],
    ['method' => 'DELETE', 'path' => '/api/admin/groups/{id}', 'handler' => 'App\Controllers\AdminController@deleteGroup', 'middleware' => ['admin']],
    
    // Admin message management
    ['method' => 'GET', 'path' => '/api/admin/messages', 'handler' => 'App\Controllers\AdminController@messages', 'middleware' => ['admin']],
    ['method' => 'DELETE', 'path' => '/api/admin/messages/{id}', 'handler' => 'App\Controllers\AdminController@deleteMessage', 'middleware' => ['admin']],
    
    // Admin settings
    ['method' => 'GET', 'path' => '/api/admin/settings', 'handler' => 'App\Controllers\AdminController@settings', 'middleware' => ['admin']],
    ['method' => 'PUT', 'path' => '/api/admin/settings', 'handler' => 'App\Controllers\AdminController@updateSettings', 'middleware' => ['admin']],
];
