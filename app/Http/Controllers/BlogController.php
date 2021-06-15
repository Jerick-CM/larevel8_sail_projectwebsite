<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Blog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $time_start = microtime(true);

        if ($request->slug) {

            $table = 'blogs';

            $query = Blog::join('users', 'users.id', '=', $table . '.user_id')
                ->where($table . '.slug', $request->slug)
                ->select('users.name', 'users.email', $table . '.id', $table . '.title', $table . '.content', $table . '.slug', $table . '.id', $table . '.publish', $table . '.created_at', $table . '.image')
                ->get();

            foreach ($query as $key => $value) {
                $query[$key]['human_date'] = Carbon::parse($value['created_at'])->diffForHumans();
                $query[$key]['image'] = url($value['image']);
            }
        }

        $time_end = microtime(true);
        $timeend = $time_end - $time_start;

        return response()->json([
            'data' => $query,
            'success' => 1,
            '_benchmark' => $timeend,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {

        $time_start = microtime(true);

        $user = User::findOrFail($request->user()->id);

        /**
         * Image upload
         *
         */

        if ($request->file('image')) {

            $filenameWithExt = $request->file('image')->getClientOriginalName();

            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('image')->getClientOriginalExtension();

            $FileNameToStore = $filename . '_' . time() . "." . $extension;

            $path = $request->file('image')->storeAs('public/upload_blog', $FileNameToStore);

            $FileNameToStore = 'storage/upload_blog/' . $FileNameToStore;
        } else {

            $FileNameToStore = null;
        }


        /**
         * Image upload
         *
         */

        $blog =  new Blog();
        if ($request->input('publish') == 1) {

            $blog->publish = 1;
            $blog->publish_text  = 'draft';
        } else {

            $blog->publish = 2;
            $blog->publish_text  = 'publish';
        }

        $newBlog = new Blog(
            [
                'title' => $request->input('title'),

                'content' => $request->input('content'),
                'slug' => Str::slug($request->input('title') . "-" . time(), '-'),
                'image' => $FileNameToStore,
                'publish' =>  $blog->publish,
                'publish_test' =>  $blog->publish_text,
                'ckeditor_log' => $request->input('ckeditor_log')
            ]
        );

        $user->blogs()->save($newBlog);

        $time_end = microtime(true);
        $timeend = $time_end - $time_start;

        return response()->json([
            'save' =>  $newBlog,
            'success' => true,
            'publish' => $blog->publish,
            'user' => $request->user(),
            'path' =>  $FileNameToStore ? url($FileNameToStore) : "",
            'path_pub' =>  $FileNameToStore ? url($path) : "",
            '_benchmark' => $timeend,
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
    public function show(Request $request, $page, $itemsperpage)
    {

        $time_start = microtime(true);


        $skip = $request->page;
        if ($page == 1) {
            $skip = 0;
        } else {
            $skip = $page * $page;
        }

        $table = 'blogs';

        if ($request->sortBy == ""  && $request->sortDesc == "") {
            $page = $page ? $page : 1;
            $limit = $itemsperpage ? $itemsperpage : 10;

            $blogs = Blog::where('blogs.publish', 2)
                ->orderBy($table . '.created_at', 'desc')
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->select('users.name', 'users.email', 'blogs.id', 'blogs.title', 'blogs.content', 'blogs.slug', 'blogs.id', 'blogs.publish', 'blogs.image', 'blogs.created_at')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($itemsperpage)
                ->get();

            $blogs_count = Blog::where('blogs.publish', 2)
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->get();
        } else {

            if ($request->sortDesc) {
                $order = 'desc';
            } else {
                $order = 'asc';
            }

            $page = $page  ? $page  : 1;
            $limit = $itemsperpage ? $itemsperpage : 10;

            $blogs = Blog::where('blogs.publish', 2)
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->select('users.name', 'users.email', 'blogs.id', 'blogs.title', 'blogs.content', 'blogs.slug', 'blogs.id', 'blogs.publish', 'blogs.image', 'blogs.created_at')
                ->orderBy($request->sortBy, $order)
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($itemsperpage)
                ->get();

            $blogs_count = Blog::where('blogs.publish', 2)
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->get();
        }

        $blogsCs =   $blogs->count();
        $blogsCount =  $blogs_count->count();

        foreach ($blogs as $key => $value) {
            $blogs[$key]['human_date'] = Carbon::parse($value['created_at'])->diffForHumans();
            $blogs[$key]['image'] = url($value['image']);
            $blogs[$key]['path'] = url($value['path']);
        }

        if ($blogsCs > 0 && $blogsCount == 0) {
            $blogsCount =   $blogsCs;
        }
        // $blogs = array_reverse($blogs);


        $time_end = microtime(true);
        $timeend = $time_end - $time_start;



        return response()->json([
            'data' => $blogs,
            'total' =>  $blogsCount,
            'skip' => $skip,
            'take' => $itemsperpage,
            '_benchmark' => $timeend,
        ], 200);
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
    public function update(Request $request, $id)
    {

        $time_start = microtime(true);

        $Blogcheck = Blog::findOrFail($id);

        if (url($Blogcheck->image) === $request->image) {
        } else if (secure_url($Blogcheck->image) === $request->image) {
        } else if ($request->image === "") {
        } else {

            if ($request->image) {
                /**
                 * Image upload Start
                 *
                 */
                $filenameWithExt = $request->file('image')->getClientOriginalName();

                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);

                $extension = $request->file('image')->getClientOriginalExtension();

                $FileNameToStore = $filename . '_' . time() . "." . $extension;

                $path = $request->file('image')->storeAs('public/upload_blog', $FileNameToStore);
                $FileNameToStore = 'storage/upload_blog/' . $FileNameToStore;

                /**
                 * Image upload End
                 *
                 */
            }
        }


        $Blog = Blog::findOrFail($id);

        $Blog->title = $request->title;
        $Blog->content = $request->content;
        $Blog->publish = $request->publish;

        if ($request->publish == 1) {
            $Blog->publish_text = 'draft';
        } else {
            $Blog->publish_text = 'publish';
        }

        if (url($Blogcheck->image) === $request->image) {
        } else if (secure_url($Blogcheck->image) === $request->image) {
        } else if ($request->image == "") {

            $Blog->image   = '';
        } else {

            if ($request->image) {

                $Blog->image = $FileNameToStore;
            } else {
            }
        }

        $Blog->update();

        $Blogagain = Blog::findOrFail($id);

        if ($request->image == "") {

            $image =  '';
        } else if (secure_url($Blogcheck->image) === $request->image) {

            $image =  secure_url($Blogagain->image);
        } else {

            $image =  url($Blogagain->image);
        }

        $time_end = microtime(true);
        $timeend = $time_end - $time_start;

        return response()->json([
            'save' => $Blog,
            'success' => 1,
            'image' =>  $image,
            'user' => $request->user(),
            'blog' =>  $Blogcheck,
            '_benchmark' => $timeend,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function delete(Request $request, $table_id)
    {
        $time_start = microtime(true);

        $table = Blog::findOrFail($table_id);
        $table->delete();

        $time_end = microtime(true);
        $timeend = $time_end - $time_start;
        return response()->json([
            'success' => 1,
            'user' => $request->user(),
            '_benchmark' => $timeend,
        ], 200);
    }

    //


    public function datatable(Request $request)
    {
        $skip = $request->page;
        if ($request->page == 1) {
            $skip = 0;
        } else {
            $skip = $request->page * $request->page;
        }

        if ($request->sortBy == ""  && $request->sortDesc == "") {

            $page = $request->has('page') ? $request->get('page') : 1;

            $limit = $request->has('itemsPerPage') ? $request->get('itemsPerPage') : 10;

            $Blogs = Blog::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['users.name', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['slug', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->select('users.name', 'users.email', 'blogs.id', 'blogs.title', 'blogs.content', 'blogs.slug', 'blogs.id', 'blogs.publish', 'blogs.image', 'blogs.created_at', 'blogs.ckeditor_log')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($request->itemsPerPage)
                ->get();

            $Blogs_count = Blog::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['users.name', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['slug', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->get();
        } else {

            if ($request->sortDesc) {
                $order = 'desc';
            } else {
                $order = 'asc';
            }

            $page = $request->has('page') ? $request->get('page') : 1;
            $limit = $request->has('itemsPerPage') ? $request->get('itemsPerPage') : 10;

            $Blogs = Blog::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['users.name', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['slug', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->select('users.name', 'users.email', 'blogs.id', 'blogs.title', 'blogs.content', 'blogs.slug', 'blogs.id', 'blogs.publish', 'blogs.image', 'blogs.created_at', 'blogs.ckeditor_log')
                ->orderBy($request->sortBy, $order)
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->take($request->itemsPerPage)
                ->get();

            $Blogs_count = Blog::where([['title', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['users.name', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['slug', 'LIKE', "%" . $request->search . "%"]])
                ->orWhere([['publish_text', 'LIKE', "%" . $request->search . "%"]])
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->get();
        }

        $BlogsCs =   $Blogs->count();
        $BlogsCount =  $Blogs_count->count();

        foreach ($Blogs as $key => $value) {
            $Blogs[$key]['human_date'] = Carbon::parse($value['created_at'])->diffForHumans();
            $Blogs[$key]['image'] = $value['image'] ? url($value['image']) : '';
            $Blogs[$key]['path'] = $value['path'] ? url($value['path']) : '';
        }

        if ($BlogsCs > 0 && $BlogsCount == 0) {
            $BlogsCount =   $BlogsCs;
        }
        // $Blogs = array_reverse($Blogs);
        return response()->json([
            'data' => $Blogs,
            'total' =>  $BlogsCount,
            'skip' => $skip,
            'take' => $request->itemsPerPage
        ], 200);
    }
}
