<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Utils\Response;
use App\Utils\Validator;

class GroupController
{
    public function index()
    {
        $currentUser = $_GET['current_user'];
        
        $groups = Group::getUserGroups($currentUser->id);
        
        foreach ($groups as &$group) {
            $group['member_count'] = GroupMember::countMembers($group['id']);
        }
        
        Response::success($groups);
    }
    
    public function store()
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = Validator::make($data, [
            'name' => 'required',
        ]);
        
        if ($validator->fails()) {
            Response::error($validator->firstError(), 400, 'VALIDATION_ERROR');
        }
        
        $group = Group::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'avatar' => $data['avatar'] ?? null,
            'owner_id' => $currentUser->id,
        ]);
        
        Response::success($group->toArray(), 'Group created');
    }
    
    public function show($id)
    {
        $currentUser = $_GET['current_user'];
        $id = (int)$id;
        
        $group = Group::findById($id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        $isMember = GroupMember::isMember($id, $currentUser->id);
        $members = GroupMember::getMembers($id);
        
        Response::success([
            'group' => $group->toArray(),
            'is_member' => $isMember,
            'members' => $members,
            'member_count' => count($members),
        ]);
    }
    
    public function join($id)
    {
        $currentUser = $_GET['current_user'];
        $id = (int)$id;
        
        $group = Group::findById($id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (GroupMember::isMember($id, $currentUser->id)) {
            Response::error('Already a member', 400, 'ALREADY_MEMBER');
        }
        
        GroupMember::create($id, $currentUser->id, GroupMember::ROLE_MEMBER);
        
        Response::success(null, 'Joined group');
    }
    
    public function leave($id)
    {
        $currentUser = $_GET['current_user'];
        $id = (int)$id;
        
        $group = Group::findById($id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (GroupMember::isOwner($id, $currentUser->id)) {
            $memberCount = GroupMember::countMembers($id);
            if ($memberCount > 1) {
                Response::error('Owner cannot leave. Transfer ownership first or disband the group.', 400, 'OWNER_CANNOT_LEAVE');
            }
            Group::delete($id);
            Response::success(null, 'Group disbanded');
        }
        
        if (!GroupMember::isMember($id, $currentUser->id)) {
            Response::error('Not a member', 400, 'NOT_MEMBER');
        }
        
        GroupMember::delete($id, $currentUser->id);
        
        Response::success(null, 'Left group');
    }
    
    public function setAdmin($groupId, $userId)
    {
        $currentUser = $_GET['current_user'];
        $groupId = (int)$groupId;
        $userId = (int)$userId;
        
        if (!GroupMember::isOwner($groupId, $currentUser->id)) {
            Response::error('Only owner can set admins', 403, 'FORBIDDEN');
        }
        
        $group = Group::findById($groupId);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (!GroupMember::isMember($groupId, $userId)) {
            Response::error('User is not a member', 404, 'NOT_MEMBER');
        }
        
        if (GroupMember::isOwner($groupId, $userId)) {
            Response::error('Cannot change owner role', 400, 'INVALID_ROLE');
        }
        
        $member = GroupMember::findMember($groupId, $userId);
        $newRole = ($member->role === GroupMember::ROLE_ADMIN) 
            ? GroupMember::ROLE_MEMBER 
            : GroupMember::ROLE_ADMIN;
        
        GroupMember::updateRole($groupId, $userId, $newRole);
        
        Response::success(['role' => $newRole], 'Admin role updated');
    }
    
    public function removeMember($groupId, $userId)
    {
        $currentUser = $_GET['current_user'];
        $groupId = (int)$groupId;
        $userId = (int)$userId;
        
        if (!GroupMember::isAdmin($groupId, $currentUser->id) && 
            !GroupMember::isOwner($groupId, $currentUser->id)) {
            Response::error('Only admins and owner can remove members', 403, 'FORBIDDEN');
        }
        
        if (GroupMember::isOwner($groupId, $userId)) {
            Response::error('Cannot remove owner', 400, 'CANNOT_REMOVE_OWNER');
        }
        
        if (!GroupMember::isMember($groupId, $userId)) {
            Response::error('User is not a member', 404, 'NOT_MEMBER');
        }
        
        GroupMember::delete($groupId, $userId);
        
        Response::success(null, 'Member removed');
    }
    
    public function sendMessage($groupId)
    {
        $currentUser = $_GET['current_user'];
        $groupId = (int)$groupId;
        $data = json_decode(file_get_contents('php://input'), true);
        
        $group = Group::findById($groupId);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (!GroupMember::isMember($groupId, $currentUser->id)) {
            Response::error('Not a member', 403, 'NOT_MEMBER');
        }
        
        if (empty($data['content'])) {
            Response::error('Content is required', 400, 'VALIDATION_ERROR');
        }
        
        $message = GroupMessage::create([
            'group_id' => $groupId,
            'user_id' => $currentUser->id,
            'content' => htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8'),
            'message_type' => $data['message_type'] ?? GroupMessage::TYPE_TEXT,
        ]);
        
        $messageData = array_merge($message->toArray(), [
            'user' => [
                'id' => $currentUser->id,
                'username' => $currentUser->username,
                'nickname' => $currentUser->nickname,
                'avatar' => $currentUser->avatar,
            ],
        ]);
        
        Response::success([
            'id' => $message->id,
            'message' => $messageData,
        ], 'Message sent');
    }
    
    public function getMessages($groupId)
    {
        $currentUser = $_GET['current_user'];
        $groupId = (int)$groupId;
        
        $group = Group::findById($groupId);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (!GroupMember::isMember($groupId, $currentUser->id)) {
            Response::error('Not a member', 403, 'NOT_MEMBER');
        }
        
        $limit = (int)($_GET['limit'] ?? 50);
        $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : null;
        
        $messages = GroupMessage::getHistory($groupId, $limit, $beforeId);
        $messages = array_reverse($messages);
        
        Response::success($messages);
    }
    
    public function update($id)
    {
        $currentUser = $_GET['current_user'];
        $id = (int)$id;
        $data = json_decode(file_get_contents('php://input'), true);
        
        $group = Group::findById($id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        if (!GroupMember::isAdmin($id, $currentUser->id)) {
            Response::error('Only admins can update group', 403, 'FORBIDDEN');
        }
        
        $group = Group::update($id, $data);
        
        Response::success($group->toArray(), 'Group updated');
    }
    
    public function disband($id)
    {
        $currentUser = $_GET['current_user'];
        $id = (int)$id;
        
        if (!GroupMember::isOwner($id, $currentUser->id)) {
            Response::error('Only owner can disband group', 403, 'FORBIDDEN');
        }
        
        Group::delete($id);
        
        Response::success(null, 'Group disbanded');
    }
}
