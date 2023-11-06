<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Validator;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Carbon;

class ChatController extends Controller
{
    public function list(Request $request)
    {
        $data = User::select(['id', 'firstName', 'lastName', 
            'imagePath', 
            DB::raw("IFNULL(
                (
                    SELECT COUNT(*)
                    FROM chat
                    WHERE
                        chat.isRead = 0
                        AND chat.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
                        AND (
                            (chat.fromUserId = users.id AND chat.toUserId = " . Auth()->user()->id . ")
                            OR (chat.fromUserId = " . Auth()->user()->id . " AND chat.toUserId = users.id)
                        )
                        AND chat.fromUserId != " . Auth()->user()->id . "
                ),
                0
            ) as unreadMessageCount"),
            DB::raw('IFNULL((SELECT fromUserId FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND ((chat.fromUserId = users.id AND chat.toUserId = ' . Auth()->user()->id . ') OR (chat.fromUserId = ' . Auth()->user()->id . ' AND chat.toUserId = users.id)) ORDER BY created_at DESC LIMIT 1), "") as fromUserIdLastMessage'),
            DB::raw('IFNULL((SELECT content FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND ((chat.fromUserId = users.id AND chat.toUserId = ' . Auth()->user()->id . ') OR (chat.fromUserId = ' . Auth()->user()->id . ' AND chat.toUserId = users.id)) ORDER BY created_at DESC LIMIT 1), "") as lastMessage'),
            DB::raw('IFNULL((SELECT created_at FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND ((chat.fromUserId = users.id AND chat.toUserId = ' . Auth()->user()->id . ') OR (chat.fromUserId = ' . Auth()->user()->id . ' AND chat.toUserId = users.id)) ORDER BY created_at DESC LIMIT 1), "") as lastMessageDate'),
            DB::raw('IFNULL((SELECT isRead FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND ((chat.fromUserId = users.id AND chat.toUserId = ' . Auth()->user()->id . ') OR (chat.fromUserId = ' . Auth()->user()->id . ' AND chat.toUserId = users.id)) ORDER BY created_at DESC LIMIT 1), "") as lastMessageIsRead')
        ])        
        
        ->where('id', '!=', $request->user()->id)
        ->orderBy('lastMessageDate', 'desc');

        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function detail(Request $request){
        $validate = Validator::make($request->all(), [
            'toUserId' => 'required|integer|exists:users,id',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        $chats = DB::table('chat as c')
        ->where(function ($query) use ($request) {
            $query->where('c.toUserId', $request->user()->id)
                ->orWhere('c.fromUserId', $request->user()->id);
        })
        ->where('c.isDeleted', 0)
        ->whereDate('c.created_at', Carbon::today())
        ->where(function ($query) use ($request) {
            $query->where('c.toUserId', $request->toUserId)
                ->orWhere('c.fromUserId', $request->toUserId);
        })
        ->select('c.id', 'c.content', 'c.file','c.toUserId', 'c.fromUserId', 'c.created_at');

        $data = paginateData($chats, $request);
        return response()->json($data);
    }
  
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'content' => 'nullable|string', 
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'toUserId' => 'required|integer|exists:users,id',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }


        $result = null;

        if($request->hasFile('file')){
            $file = $request->file('file');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('chat'), $fileName);
            $result = Chat::create([
                'content' => '📷',
                'toUserId' => $request->toUserId,
                'fromUserId' => $request->user()->id,
                'updated_at' => Carbon::now(),
                'file' => $fileName,
            ]);
        }else{
            $result = Chat::create([
                'content' => $request->content,
                'toUserId' => $request->toUserId,
                'fromUserId' => $request->user()->id,
                'updated_at' => Carbon::now(),
            ]);
        }

        $getFromUser = User::select(['id', 'firstName', 'lastName', 
            'imagePath', 
            DB::raw("IFNULL((SELECT COUNT(*) FROM chat WHERE chat.isRead = 0 AND chat.created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id)), 0) as unreadMessageCount"),
            DB::raw('IFNULL((SELECT content FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.created_at DESC LIMIT 1), "") as lastMessage'),
            DB::raw('IFNULL((SELECT created_at FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.created_at DESC LIMIT 1), "") as lastMessageDate'),
            DB::raw('IFNULL((SELECT fromUserId FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.fromUserId DESC LIMIT 1), "") as fromUserIdLastMessage'),
            DB::raw('IFNULL((SELECT isRead FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND ((chat.fromUserId = users.id AND chat.toUserId = ' . Auth()->user()->id . ') OR (chat.fromUserId = ' . Auth()->user()->id . ' AND chat.toUserId = users.id)) ORDER BY created_at DESC LIMIT 1), "") as lastMessageIsRead')            
        ])->whereIn('id', [$request->user()->id, $request->toUserId])->get();


        $resultMerge = [];
        $resultMerge['user'] = $getFromUser;
        $resultMerge['chat'] = $result;

        broadcast(new \App\Events\SendMessage($resultMerge, $result->toUserId));
        broadcast(new \App\Events\SendMessage($resultMerge, $result->fromUserId));
    
        return responseSuccess($resultMerge);
    }

    public function read(Request $request){
        $validate = Validator::make($request->all(), [
            'toUserId' => 'required|integer|exists:users,id',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        Chat::where('toUserId', $request->user()->id)
        ->where('fromUserId', $request->toUserId)
        ->update([
            'isRead' => 1,
            'updated_at' => Carbon::now(),
        ]);

        $result = Chat::where('toUserId', $request->user()->id)
        ->where('fromUserId', $request->toUserId)
        ->orderBy('created_at', 'desc')
        ->first();

        if($result){
            $getFromUser = User::select(['id', 'firstName', 'lastName', 
                'imagePath', 
                DB::raw("IFNULL((SELECT COUNT(*) FROM chat WHERE chat.isRead = 0 AND chat.created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id)), 0) as unreadMessageCount"),
                DB::raw('IFNULL((SELECT content FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.created_at DESC LIMIT 1), "") as lastMessage'),
                DB::raw('IFNULL((SELECT created_at FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.created_at DESC LIMIT 1), "") as lastMessageDate'),
                DB::raw('IFNULL((SELECT fromUserId FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.fromUserId DESC LIMIT 1), "") as fromUserIdLastMessage'),
                DB::raw('IFNULL((SELECT isRead FROM chat WHERE created_at > NOW() - INTERVAL 1 DAY AND (chat.fromUserId = users.id OR chat.toUserId = users.id) ORDER BY chat.fromUserId DESC LIMIT 1), "") as lastMessageIsRead'),                
                ])->whereIn('id', [$request->user()->id, $request->toUserId])->get();


            $resultMerge = [];
            $resultMerge['user'] = $getFromUser;
            $resultMerge['chat'] = $result;
            $resultMerge['status'] = 'read';

            broadcast(new \App\Events\ReadMessage($resultMerge, $result->toUserId));
            broadcast(new \App\Events\ReadMessage($resultMerge, $result->fromUserId));

            
        }
        return responseSuccess($result);
    }

   
}
