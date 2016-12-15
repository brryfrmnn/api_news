<?php

namespace App\Modules\Post\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Post\Model\Comment;
use Centaur\AuthManager;

class CommentController extends Controller
{
    protected $authManager;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function index(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'q' => 'string',
            'result_type' => 'string',
            'offset' => 'integer',
            'post_id'=>'string',
            'include_user' => 'boolean',
            'include_admin' => 'boolean' 
        ]);
        if ($validator->passes()) {
            $q              = $request->q;
            $result_type    = $request->result_type; //username, post, first_name, email, word
            $offset         = $request->input('offset',10);
            $post_id        = $request->post_id;
            $include_user   = $request->include_user;
            $include_admin  = $request->include_admin;
            
            $comment = Comment::findByPost($post_id);
            
            if ($q != null) {
                $comment->search($q,$result_type);
            }
            $comment->orderBy('id','desc');
            $comment = $comment->paginate($offset)->appends($request->input());
            if ($include_user) {
                $comment->load('user');
            }
            if ($include_admin) {
                $comment->load('admin');
            }
            $meta['status'] = true;
            $meta['message'] = "List All Comment";
            $meta['total'] = $comment->total();
            $meta['offset'] = $comment->perPage();
            $meta['current'] =$comment->currentpage();
            $meta['last']=$comment->lastPage();
            $meta['next']=$comment->nextPageUrl();
            $meta['prev']=$comment->previousPageUrl();
            $data = $comment->all();    
        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'comment' => 'required|string',
                'post_id' => 'required|integer|exists:posts,id',
                'email' => 'required|string',
                'full_name' => 'required|string',
                'admin_id' => 'integer',
        ]);
        if ($validator->passes()) {
            $comment      = $request->comment;
            $post_id      = $request->post_id;
            $admin_id     = $request->admin_id;
            $email        = $request->email;
            $full_name    = $request->full_name;

            $name = explode(' ', $full_name);
            $first_name = $name[0];
            $last_name = '';
            if(count($name) > 1) {
                    unset($name[0]);
                    $last_name = implode(' ', $name);
            } 

            $credential = [
                    'email' => $email
            ];
            $user = \Sentinel::findByCredentials($credential);
            // dd($user@);
            if ($user==null) {
                $credentials = [
                    'email' => trim(strtolower($email)),
                    'password' => 'subscriber',
                    'username' => trim(strtolower(str_replace(' ', '_', $email))),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                ];
                // // Attempt the Register
                $result = $this->authManager->register($credentials,true);
                if ($result->isFailure()) {
                    //belum ada apa2
                    $result->setMessage('Registration Failed. .'); 
                } else {
                    $role = \Sentinel::findRoleBySlug('subscriber');
                    $role->users()->attach($result->user);
                    //define user_id, for comment
                    $user_id = $result->user->id;
                    // event(new UserRegisteredEvent($result->user));
                    // Ask the user to check their email for the activation link
                    $result->setMessage('Registration complete. .');
                    // There is no need to send the payload data to the end user
                    $result->clearPayload();

                }
            } else {
                $user_id = $user->id;
            }
            try 
            {
                                
                $data = new Comment;
                $data->comment        = $comment;
                $data->post_id        = $post_id;
                $data->user_id        = $user_id;
                $data->admin_id       = $admin_id;
        
                if ($data->save()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success creating comment";    
                }
                
            } 
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
            {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }
            catch (\Illuminate\Database\QueryException $e) {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }

        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        
        $validator = \Validator::make($request->all(), [
                'id'       => 'required|integer',
        ]);
        if ($validator->passes()) {
            $id        = $request->id;

            try 
            {
               
                $comment = Comment::findOrFail($id);
                $meta['status'] = true;
                $meta['message'] = "Success showing comment ID #".$id;
                $data = $comment;       
                           
            } 
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
            {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }
            catch (\Illuminate\Database\QueryException $e) {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }

        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    
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
    public function update(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'comment' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'admin_id' => 'integer',
        ]);
        if ($validator->passes()) {
            $text      = $request->comment;
            // $post_id      = $request->post_id;
            $user_id      = $request->user_id;
            $admin_id     = $request->admin_id;
            $id           = $request->id;

            try 
            {             
                $comment = Comment::findOrFail($id);
                if ($comment->user_id == $user_id) {
                    $comment->comment    = $text;
                    if ($admin_id!= null) {
                        $comment->admin_id  = $admin_id;    
                    }       
                    if ($comment->save()) {
                        $meta['status'] = true;
                        $meta['message'] = "Success updating comment";    
                    }
                } else {
                    $meta['status'] = false;
                    $meta['message'] = "Access denied";
                }   
            } 
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
            {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }
            catch (\Illuminate\Database\QueryException $e) {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }

        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'id' => 'required|integer',
                'admin_id' => 'integer',
                'user_id' => 'integer'
        ]);
        if ($validator->passes()) {
            $id       = $request->id;
            $admin_id = $request->admin_id;
            $writer_id= $request->writer_id;

            try 
            {
                $comment = Comment::findOrFail($id);
                if ($writer_id == $comment->writer_id) {
                    if ($comment->delete()) {
                        $meta['status'] = true;
                        $meta['message'] = "Success deleting comment"; 
                    }
                } else {
                    $meta['status'] = true;
                    $meta['message'] = "access denied";
                }
            } 
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
            {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }
            catch (\Illuminate\Database\QueryException $e) {
                $meta['status'] = false;
                $meta['message'] = 'Error '.$e;
            }
            
        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed delete comment";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }
}
