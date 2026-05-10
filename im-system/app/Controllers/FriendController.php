<?php

namespace App\Controllers;

use App\Models\Friendship;
use App\Models\User;
use App\Services\CacheService;
use App\Utils\Response;
use App\Utils\Validator;

class FriendController
{
    public function sendRequest()
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['friend_id'])) {
            Response::error('friend_id is required', 400, 'VALIDATION_ERROR');
        }
        
        $friendId = (int)$data['friend_id'];
        
        if ($friendId === $currentUser->id) {
            Response::error('Cannot add yourself as friend', 400, 'INVALID_REQUEST');
        }
        
        $friend = User::findById($friendId);
        if (!$friend) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        $existingFriendship = Friendship::findFriendship($currentUser->id, $friendId);
        if ($existingFriendship) {
            if ($existingFriendship->status === Friendship::STATUS_ACCEPTED) {
                Response::error('Already friends', 400, 'ALREADY_FRIENDS');
            } elseif ($existingFriendship->status === Friendship::STATUS_PENDING) {
                Response::error('Request already pending', 400, 'REQUEST_PENDING');
            }
        }
        
        $friendship = Friendship::create($currentUser->id, $friendId);
        
        Response::success([
            'id' => $friendship->id,
            'friend_id' => $friendId,
            'status' => 'pending',
        ], 'Friend request sent');
    }
    
    public function handleRequest($id)
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['status'])) {
            Response::error('Status is required', 400, 'VALIDATION_ERROR');
        }
        
        $request = Friendship::findById($id);
        if (!$request) {
            Response::error('Request not found', 404, 'NOT_FOUND');
        }
        
        if ($request->friend_id !== $currentUser->id) {
            Response::error('Not authorized to handle this request', 403, 'FORBIDDEN');
        }
        
        if ($request->status !== Friendship::STATUS_PENDING) {
            Response::error('Request already processed', 400, 'ALREADY_PROCESSED');
        }
        
        if ($data['status'] === 'accept') {
            Friendship::accept($id);
            Response::success(null, 'Friend request accepted');
        } elseif ($data['status'] === 'reject') {
            Friendship::reject($id);
            Response::success(null, 'Friend request rejected');
        } else {
            Response::error('Invalid status. Use "accept" or "reject"', 400, 'INVALID_STATUS');
        }
    }
    
    public function index()
    {
        $currentUser = $_GET['current_user'];
        
        $friends = Friendship::getFriends($currentUser->id);
        
        foreach ($friends as &$friend) {
            $friend['is_online'] = CacheService::isOnline($friend['id']);
        }
        
        Response::success($friends);
    }
    
    public function pending()
    {
        $currentUser = $_GET['current_user'];
        
        $requests = Friendship::getPendingRequests($currentUser->id);
        
        Response::success($requests);
    }
    
    public function delete($friendId)
    {
        $currentUser = $_GET['current_user'];
        
        $friendId = (int)$friendId;
        
        if (!Friendship::isFriend($currentUser->id, $friendId)) {
            Response::error('Not friends', 400, 'NOT_FRIENDS');
        }
        
        Friendship::delete($currentUser->id, $friendId);
        
        Response::success(null, 'Friend removed');
    }
    
    public function search()
    {
        $keyword = $_GET['keyword'] ?? '';
        
        if (strlen($keyword) < 1) {
            Response::error('Search keyword is required', 400, 'VALIDATION_ERROR');
        }
        
        $result = User::search($keyword);
        
        foreach ($result['list'] as &$user) {
            $user['is_online'] = CacheService::isOnline($user['id']);
        }
        
        Response::success($result['list']);
    }
}
