<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Ckeditorupload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $time_start = microtime(true);
        $time_end = microtime(true);
        $timeend = $time_end - $time_start;

        return response()->json([
            'success' => true,
            '_elapsed_time' => $timeend,
            $request->user()
            // 'errors' => $validator->errors(),
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $user = User::findOrFail($request->user()->id);

        $newpost = new Post(
            [
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'slug' => Str::slug($request->input('title') . "-" . time(), '-')
            ]
        );

        $user->posts()->save($newpost);

        $time_start = microtime(true);
        $time_end = microtime(true);
        $timeend = $time_end - $time_start;

        return response()->json([
            'success' => true,
            '_elapsed_time' => $timeend,
            'user' => $request->user(),
            'user_id' => $request->user()->id,
            'data' => $request->input('name')

        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $user = User::findOrFail($id);

        foreach ($user->posts as $post) {

            echo $post->title . " " . $post->content . '<br/>';
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $user_id, $post_id)
    {

        // var_dump($user_id);
        // echo '<br>';
        // var_dump($post_id);
        // die();
        $user = User::findOrFail($user_id);

        $user->posts()->whereId($post_id)->update(['title' => 'my title', 'content' => 'my content']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($user_id, $post_id)
    {

        // $datum  = FaqFile::find($id);
        // $datum->delete();

        $user = User::findOrFail($user_id);

        $user->posts()->whereId($post_id)->delete();
    }


    public function ckeditor(Request $request)
    {

        $filenameWithExt = $request->file('upload')->getClientOriginalName();

        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);

        $extension = $request->file('upload')->getClientOriginalExtension();

        $FileNameToStore = $filename . '_' . time() . "." . $extension;

        $path = $request->file('upload')->storeAs('public/upload_ckeditor', $FileNameToStore);

        $data["domain"] = $_SERVER['SERVER_NAME'];
        $FileNameToStore = 'storage/upload_ckeditor/' . $FileNameToStore;

        $mydata["ret"] =  $filenameWithExt;

        $image_log = new Ckeditorupload;
        $image_log->user_id = $request->user()->id;
        $image_log->image_name = $path;
        $image_log->save();


        return response()->json([
            'data' => $request,
            'user' => $request->user(),
            'success' => 1,
            'path' => $path,
            'url' =>  $mydata["url"] =  url($FileNameToStore)
        ], 200);
    }

    public function datatable(Request $request)
    {

        // $user->posts()->whereId($post_id)->delete();
        // $posts = DB::table('posts')
        // ->join('users', 'users.id', '=', 'posts.user_id')
        // // ->join('orders', 'users.id', '=', 'orders.user_id')
        // ->select('users.name','users.email', 'posts.id', 'posts.title', 'posts.content','posts.slug')
        // ->get();

        $skip = $request->page;
        if ($request->page == 1) {
            $skip = 0;
        } else {
            $skip = $request->page * $request->page;
        }

        if ($request->sortBy == ""  && $request->sortDesc == "") {
            $page = $request->has('page') ? $request->get('page') : 1;
            $limit = $request->has('itemsPerPage') ? $request->get('itemsPerPage') : 10;

            $posts = Post::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('users.name', 'users.email', 'posts.id', 'posts.title', 'posts.content', 'posts.slug')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($request->itemsPerPage)
                ->get();

            $posts_count = Post::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('users.name', 'users.email', 'posts.id', 'posts.title', 'posts.content', 'posts.slug')
                ->get();
        } else {

            if ($request->sortDesc) {
                $order = 'desc';
            } else {
                $order = 'asc';
            }

            $page = $request->has('page') ? $request->get('page') : 1;
            $limit = $request->has('itemsPerPage') ? $request->get('itemsPerPage') : 10;

            $posts = Post::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('users.name', 'users.email', 'posts.id', 'posts.title', 'posts.content', 'posts.slug')
                ->orderBy($request->sortBy, $order)
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($request->itemsPerPage)
                ->get();

            $posts_count = Post::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('users.name', 'users.email', 'posts.id', 'posts.title', 'posts.content', 'posts.slug')
                ->get();
        }


        $postsCount =  $posts_count->count();

        return response()->json([
            'items' =>  $posts->toArray(),
            'data' => $posts,
            'total' =>  $postsCount,
            'skip' => $skip,
            'take' => $request->itemsPerPage
        ], 200);
    }
}
