<?php
require_once 'App.php';

use App\App;
use App\Models\Post;
use Rakit\Validation\Validator as Validator;

return [
    /**
     * @SWG\Get(
     *     path="/",
     *     description="Return main page, where you can see routing info",
     *     operationId="mainPage",
     *     produces={
     *          "application/json",
     *     },
     *
     *
     *     @SWG\Response(
     *          response=200,
     *          description="If user authorized"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="When user not authorized"
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="If Error"
     *     ),
     *
     *          )
     *     )
     * )
     *
     *
     * @return string
     */
    [
        'method' => 'GET',
        'pattern' => '/',
        'handler' => function ($request) {
            return App::render('main');
        }
    ],

    [
        'method' => 'GET',
        'pattern' => '/total',
        'handler' => function ($request) {
            return App::json(["total" => Post::count()], 200);
        }
    ],

    /**
     * @SWG\Get(
     *     path="/posts",
     *     description="Returns all existing posts",
     *     operationId="getAll",
     *     produces={
     *          "application/json"
     *     },
     *
     *
     *     @SWG\Response(
     *          response=200,
     *          description="If user authorized"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="When user not authorized"
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="If Error"
     *
     *
     *          )
     *     )
     * )
     *
     *
     *
     */

    [
        'method' => 'GET',
        'pattern' => '/posts',
        'handler' => function ($request) {
            //case with sorting and limit
            $total = Post::count();
            if (isset($request['query']['column']) &&
                isset($request['query']['option']) &&
                isset($request['query']['offset'])) {
                $column = $request['query']['column'];
                $option = $request['query']['option'];
                $offset = (int)$request['query']['offset'];
                if ($offset >= $total) {
                    return App::json('', 422);
                } else {
                    return App::json([
                        "response" => Post::query()->skip($offset)->orderBy($column, $option)->take(10)->get(),
                        "total" => $total
                        ], 200);
                }
                //case with limit without sorting
            } elseif ((isset($request['query']['offset']))) {
//                $limit = $request['query']['limit'];
                $offset = $request['query']['offset'];


                if ($offset >= $total) {
                    return App::json('', 422);
                } else {
                    return App::json([
                        "response" => Post::query()->skip($offset)->limit(10)->get(),
                        "total" => $total
                    ], 200);
                }

                //default case
            } else {
                $limit = 5;
                return App::json(Post::query()->limit($limit)->get(), 200);
            }
        }
    ],

    /**
     * @SWG\Get(
     *     path="/posts/{id}",
     *     description="Return a post by ID, if user authorized",
     *     operationId="getAction",
     *     produces={
     *          "application/json"
     *     },
     *
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          description="ID of post to get",
     *          required=true,
     *          type="integer"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="If post fetched successfully"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="When user not authorized"
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="If post ID doesn't exists"
     *
     *          )
     *     )
     * ))
     *
     * @param int $id
     *
     * @return int
     */
    /**
     * domain/posts/ID - where ID = Post ID that you want to get
     */
    [
        'method' => 'GET',
        'pattern' => '/posts/(\d+)',
        'handler' => function ($request, $id) {
            return App::json(Post::findOrFail($id), 200);
        }
    ],

    /**
     * Create new Post
     *
     * @SWG\POST(
     *     path="/posts/create",
     *     description="Create new post use send post data",
     *     operationId="postAction",
     *     produces={
     *          "application/json"
     *     },
     *     @SWG\Parameter(
     *          name="title",
     *          in="formData",
     *          description="Title for new post",
     *          required=true,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="content",
     *          in="formData",
     *          description="Content for new post",
     *          required=true,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="author",
     *          in="formData",
     *          description="Author of new post",
     *          required=true,
     *          type="string"
     *      ),
     *     @SWG\Response(
     *          response=200,
     *          description="If new post created successfully"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="If user not authenticated"
     *     ),
     *     @SWG\Response(
     *          response=409,
     *          description="If post data is not valid"
     *     ),
     *
     * )
     *
     * @return array
     */
    [
        'method' => 'POST',
        'pattern' => '/posts/create',
        'handler' => function ($request) {
            $rules = [
                'id' => 'numeric',
                'title' => 'required|min:3',
                'content' => 'required|min:2',
                'author' => 'required|min:2',
            ];
            App::validator($request, $rules);
            if (App::$errors) {
                return App::json(App::$errors, 422);
            } else {
                $title = htmlspecialchars($request['json']['title']);
                $content = htmlspecialchars($request['json']['content']);
                $author = htmlspecialchars($request['json']['author']);
                $data = [
                    'title' => App::mb_ucfirst($title),
                    'content' => App::mb_ucfirst($content),
                    'author' => App::mb_ucfirst($author)
                ];

                $post = Post::create($data);

                return App::json($post, 200);
            }
        }
    ],

    /**
     * @SWG\DELETE(
     *     path="/posts/delete/{id}",
     *     description="Delete a post by ID, if user authorized",
     *     operationId="deleteAction",
     *     produces={
     *          "application/json",
     *     },
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          description="ID of the post, you want to get",
     *          required=true,
     *          type="integer",
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="If post was deleted successfully"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="When user not authorized"
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="If Error"
     *
     *
     *          )
     *     )
     * )
     *
     * @param int $id
     *
     * @return int
     */

    [
        'method' => 'DELETE',
        'pattern' => "/posts/delete/(\d+)",
        'handler' => function ($request, $id) {
            $post = Post::findOrFail($id);

            if (!$post) {
                return json_encode(['error' => 'id ' . $id . ' not found']);
            }
            if ($post->delete()) {
                return App::json(['success'], 200);
            }
        }
    ],


    /**
     * Edit an existing post
     *
     * @SWG\PUT(
     *     path="/posts/edit/{id}",
     *     description="Create new post use send post data",
     *     operationId="editAction",
     *     produces={
     *          "application/json"
     *     },
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          description="PostID required",
     *          required=true,
     *          type="integer"
     *     ),
     *     @SWG\Parameter(
     *          name="title",
     *          in="formData",
     *          description="Title for new post",
     *          required=false,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="content",
     *          in="formData",
     *          description="Content for new post",
     *          required=false,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="author",
     *          in="formData",
     *          description="Author of new post",
     *          required=false,
     *          type="string"
     *     ),
     *     @SWG\Response(
     *          response=200,
     *          description="If new post created successfully"
     *     ),
     *     @SWG\Response(
     *          response=401,
     *          description="If user not authenticated"
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="If post data is empty"
     *     ),
     *
     * )
     *
     * @return array
     */

    [
        'method' => 'PUT',
        'pattern' => '/posts/edit/(\d+)',
        'handler' => function ($request, $id) {

            $rules = [
                //validation rules
                'title' => 'min:2',
                'content' => 'min:2',
                'author' => 'min:2'
            ];
            App::validator($request, $rules);

            $request_data = $request['json'];
            $post = Post::find($id);
            if (!$post) {
                return App::json(['error' => 'id ' . $id . ' not found'], 404);
            }
            if (isset($request_data['title'])) {
                $post->title = $request_data['title'];
            }
            if (isset($request_data['content'])) {
                $post->content = $request_data['content'];
            }
            if (isset($request_data['author'])) {
                $post->author = $request_data['author'];
            }
            if (isset($request_data['created_at'])) {
                $post->created_at = $request_data['created_at'];
            }
            $post->save();
            if (App::$errors) {
                return App::json(App::$errors, 422);
            } else {
                return App::json($post, 200);
            }
        }
    ],
    [
        'method' => 'GET',
        'pattern' => '/sort/title/desc',
        'handler' => function ($request) {
            $sorted = Post::all()->sortByDesc('id')->toArray();
            $json = json_encode($sorted);

            return $sorted;
        }
    ],
];