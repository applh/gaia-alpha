<?php
namespace Chat\Controller;

use GaiaAlpha\Framework;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Model\Message;
use GaiaAlpha\Model\User;
use GaiaAlpha\Controller\BaseController;

class ChatController extends BaseController
{
    public function registerRoutes()
    {
        Router::add('GET', '/@/api/chat/users', [$this, 'getUsers']);
        Router::add('GET', '/@/api/chat/messages/(\d+)', [$this, 'getMessages']);
        Router::add('POST', '/@/api/chat', [$this, 'sendMessage']);
        Router::add('PATCH', '/@/api/chat/read/(\d+)', [$this, 'markRead']);
    }

    public function getUsers()
    {
        \GaiaAlpha\Session::requireLevel();

        $currentUserId = $_SESSION['user_id'];
        $users = User::findAll();
        $unreadCounts = Message::getUnreadCounts($currentUserId);

        $result = [];
        foreach ($users as $user) {
            if ($user['id'] == $currentUserId)
                continue;

            $result[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'unread' => $unreadCounts[$user['id']] ?? 0,
                // Could add 'last_active' later if we track it
            ];
        }

        Response::json($result);
    }

    public function getMessages($otherUserId)
    {
        \GaiaAlpha\Session::requireLevel();

        $currentUserId = $_SESSION['user_id'];
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;

        $messages = Message::getConversation($currentUserId, $otherUserId, $limit);

        // Auto-mark as read when fetching? Or explicit call?
        // Let's do explicit call or separate logic, but usually fetching implies reading.
        // For polling, fetching repeatedly shouldn't clear 'unread' status incorrectly if multiple devices etc. 
        // But for this simple app, fetching = reading is fine.
        // ACTUALLY, if we poll, we fetch often. We shouldn't mark read on every poll unless the UI says "I am looking at this".
        // Let's leave markRead to a separate call/action.

        Response::json($messages);
    }

    public function sendMessage()
    {
        \GaiaAlpha\Session::requireLevel();

        $data = \GaiaAlpha\Request::input();
        $currentUserId = $_SESSION['user_id'];

        if (empty($data['to']) || empty($data['content'])) {
            Response::json(['error' => 'Missing recipient or content'], 400);
            return;
        }

        $id = Message::create([
            'sender_id' => $currentUserId,
            'receiver_id' => $data['to'],
            'content' => $data['content']
        ]);
        Response::json(['success' => true, 'id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function markRead($senderId)
    {
        \GaiaAlpha\Session::requireLevel();
        $currentUserId = $_SESSION['user_id'];

        Message::markAsRead($senderId, $currentUserId);
        Response::json(['success' => true]);
    }
}
