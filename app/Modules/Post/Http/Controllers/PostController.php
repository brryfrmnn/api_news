<?php

namespace App\Modules\Post\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Post\Model\Post;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'q' => 'string',
            'result_type' => 'string',
            'offset' => 'integer',
            'category'=>'string',
            'filter' => 'string',
            'sort'  => 'string'
        ]);

        if ($validator->passes()) {
            $q              = $request->q;
            $result_type    = $request->result_type;
            $offset         = $request->input('offset',10);
            $category       = $request->category;
            $filter         = $request->filter;
            $sort           = $request->sort;
            
            $post = Post::with('category','writer','editor','admin')->filter($filter);
            if ($q!=null) {
                $post->search($q,$result_type);
            }
            if ($category != null) {
                $post->byCategory($category);
            }
            if ($sort!=null) {
                $post->sort($sort);
            }

            $post = $post->paginate($offset)->appends($request->input());

            $meta['status'] = true;
            $meta['message'] = "List All Post";
            $meta['total'] = $post->total();
            $meta['offset'] = $post->perPage();
            $meta['current'] =$post->currentpage();
            $meta['last']=$post->lastPage();
            $meta['next']=$post->nextPageUrl();
            $meta['prev']=$post->previousPageUrl();
            $meta['from'] = $post->firstItem();
            $meta['to'] = $post->lastItem();
            $data = $post->all();    
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
                'title' => 'required|string',
                'category_id' => 'integer',
                'image' => 'image',
                'article' => 'required|string|min:3',
                'writer_id' => 'required|integer',
                'admin_id' => 'integer',
                'editor_id' => 'integer',
                'type'  => 'string'
        ]);
        if ($validator->passes()) {
            $title      = $request->title;
            $category_id= $request->category_id;
            $article    = $request->article;
            $writer_id  = $request->writer_id;
            $admin_id   = $request->admin_id;
            $status     = 0;
            $image      = $request->file('image');
            $type       = $request->type;
            $url        = $request->url;
            
            try 
            {
                //upload image to storage
                if ($image!= null && $type == 'image') {
                    $extension  = $image->getClientOriginalExtension();
                    $fileName      = str_replace(' ', '_', $title).uniqid();
                    $mergeFileName = $fileName.'.'.$extension;
                    $destination   = storage_path('app/public/');
                    
                    $image = \Storage::put(
                        'public/'.$mergeFileName,
                        file_get_contents($request->file('image')->getRealPath())
                    );
                    //resize image
                    $resize = \Image::make(asset('storage/'.$mergeFileName));
                    $resize->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($destination . $fileName . '_square800.' . $extension);
                    //crop image
                    $resize->fit(500, 500)->save($destination . $fileName . '_square500.' . $extension);
                    $resize->fit(1502, 796)->save($destination . $fileName . '_square1502.' . $extension);
                    $resize->fit(847, 415)->save($destination . $fileName . '_square847.' . $extension);
                    $resize->fit(540, 270)->save($destination . $fileName . '_square540.' . $extension);
                    $resize->fit(280, 140)->save($destination . $fileName . '_square280.' . $extension);

                } else if ($type == 'video' && $url !== null) {
                     
                    $content = $this->getMediaInfo($url, $type);
                    
                    if ($content !== false) 
                    {
                        $content = json_encode($content);
                    }
                    else
                    {
                        $content = null;         
                    }
                } else {
                    $content = null;
                }
                
                $post = new Post;
                $post->title        = $title;
                $post->category_id  = $category_id;
                $post->article      = $article;
                $post->status       = 0;
                if (isset($mergeFileName)) {
                    $mergeFileName  = ['file' => $mergeFileName];
                    $post->content  = json_encode($mergeFileName);   
                } else if ($content!= null) {
                    $post->content  = $content;
                }
                $post->type         = $type;
                $post->writer_id    = $writer_id;
                if ($post->save()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success creating post";    
                    $data = $post;
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
                'id'       => 'required',
        ]);
        if ($validator->passes()) {
            $id        = $request->id;

            try 
            {
                if (is_numeric($id)) {
                    $post = Post::with('category','writer','editor','admin')->findOrFail($id);
                    $meta['status'] = true;
                    $meta['message'] = "Success showing post ID #".$id;
                    $data = $post;       
                } else if (is_string($id)) {
                    $post = Post::with('category','writer','editor','admin')->findBySlug($id);
                    $meta['status'] = true;
                    $meta['message'] = "Success showing post";
                    $data = $post;
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
                'id' => 'required|integer',
                'title' => 'required|string',
                'category_id' => 'integer',
                'image' => 'image',
                'article' => 'required|string|min:300',
                'writer_id' => 'required|integer',
                'admin_id' => 'integer',
                'editor_id' => 'integer'
        ]);
        if ($validator->passes()) {
            $id         = $request->id;
            $title      = $request->title;
            $category_id= $request->category_id;
            $article    = $request->article;
            $writer_id  = $request->writer_id;
            $admin_id   = $request->admin_id;
            $status     = $request->status;
            $image      = $request->file('image');
            
            try 
            {
                //upload image to storage
                $extension  = $image->getClientOriginalExtension();
                $fileName      = str_replace(' ', '_', $title).uniqid();
                $mergeFileName = $fileName.'.'.$extension;
                $destination   = storage_path('app/public/');
                if ($image != null) {
                    $image = \Storage::put(
                        'public/'.$mergeFileName,
                        file_get_contents($request->file('image')->getRealPath())
                    );
                    //resize image
                    $resize = \Image::make(asset('storage/'.$mergeFileName));
                    $resize->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($destination . $fileName . '_resize800.' . $extension);
                    //crop image
                    $resize->fit(500, 500)->save($destination . $fileName . '_square500.' . $extension);
                }

                $post = Post::findOrFail($id);
                $post->title        = $title;
                $post->category_id  = $category_id;
                $post->article      = $article;
                $post->status       = 0;
                if (isset($mergeFileName)) {
                    $post->image    = $mergeFileName;   
                }
                $post->writer_id    = $writer_id;
                if ($post->save()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success updating post";  
                    $data = $post;  
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

    public function updateStatus(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'id' => 'required|integer',
                'admin_id' => 'integer',
                'editor_id' => 'required|integer|exists:users,id',
                'status' => 'required|integer'
        ]);
        if ($validator->passes()) {
            $id = $request->id;
            $admin_id = $request->admin_id;
            $editor_id = $request->editor_id;
            $status   = $request->status;
            try 
            {
                $post = Post::findOrFail($id);
                $post->status = $status;
                if ($post->save()) {
                    $meta['status'] = true;
                    $meta['message'] = 'Success Update status';
                    $data  = $post;
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
    public function destroy($id)
    {
        try 
        {
            $post = Post::findOrFail($id);
            if ($post->delete()) {
                $meta['status'] = true;
                $meta['message'] = "Success deleting post"; 
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
                    
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }

    public function setFeatured(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'id' => 'integer',
                'admin_id' => 'integer'
        ]);
        if ($validator->passes()) {
            $id = $request->id;
            $post_id = $request->post_id;

            $count = Post::Featured();
            $count = $count->count();
            if ($count >= 5) {
                $meta['status'] = true;
                $meta['message'] = "Featured Post is Full";       
            } else {
                try 
                {
                    $post = Post::findOrFail($id);
                    $post->featured = 1;
                    if ($post->save()) {
                        $meta['status'] = true;
                        $meta['message'] = 'Success creating featured post';       
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

    public function unsetFeatured(Request $request)
    {
        $validator = \Validator::make($request->all(), [
                'id' => 'integer',
                'admin_id' => 'integer'
        ]);
        if ($validator->passes()) {
            $id = $request->id;
            $post_id = $request->post_id;
            try 
            {
                $post = Post::findOrFail($id);
                $post->featured = 0;
                if ($post->save()) {
                    $meta['status'] = true;
                    $meta['message'] = 'Success unset featured post';       
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

    public function getFeatured()
    {
        try 
        {
            $featured = Post::with('admin','writer','editor','category')->Featured(); 

            $meta['status'] = true;
            $meta['message'] = "Showing all featured post";
            // $meta['error'] = $validator->errors();
            $data = $featured;
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
               
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }

    protected function getMediaInfo($mediaUrl, $type) {
        $mediaUrl = trim($mediaUrl);
        
        
        if($type == 'video') {
            preg_match('~(?#!js YouTubeId Rev:20160125_1800)
                # Match non-linked youtube URL in the wild. (Rev:20130823)
                https?://          # Required scheme. Either http or https.
                (?:[0-9A-Z-]+\.)?  # Optional subdomain.
                (?:                # Group host alternatives.
                  youtu\.be/       # Either youtu.be,
                | youtube          # or youtube.com or
                  (?:-nocookie)?   # youtube-nocookie.com
                  \.com            # followed by
                  \S*?             # Allow anything up to VIDEO_ID,
                  [^\w\s-]         # but char before ID is non-ID char.
                )                  # End host alternatives.
                ([\w-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                (?=[^\w-]|$)       # Assert next char is non-ID or EOS.
                (?!                # Assert URL is not pre-linked.
                  [?=&+%\w.-]*     # Allow URL (query) remainder.
                  (?:              # Group pre-linked alternatives.
                    [\'"][^<>]*>   # Either inside a start tag,
                  | </a>           # or inside <a> element text contents.
                  )                # End recognized pre-linked alts.
                )                  # End negative lookahead assertion.
                [?=&+%\w.-]*       # Consume any URL (query) remainder.
                ~ix', $mediaUrl, $matchFound);

            if ($matchFound) {

                $videoId = preg_replace('~(?#!js YouTubeId Rev:20160125_1800)
                # Match non-linked youtube URL in the wild. (Rev:20130823)
                https?://          # Required scheme. Either http or https.
                (?:[0-9A-Z-]+\.)?  # Optional subdomain.
                (?:                # Group host alternatives.
                  youtu\.be/       # Either youtu.be,
                | youtube          # or youtube.com or
                  (?:-nocookie)?   # youtube-nocookie.com
                  \.com            # followed by
                  \S*?             # Allow anything up to VIDEO_ID,
                  [^\w\s-]         # but char before ID is non-ID char.
                )                  # End host alternatives.
                ([\w-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                (?=[^\w-]|$)       # Assert next char is non-ID or EOS.
                (?!                # Assert URL is not pre-linked.
                  [?=&+%\w.-]*     # Allow URL (query) remainder.
                  (?:              # Group pre-linked alternatives.
                    [\'"][^<>]*>   # Either inside a start tag,
                  | </a>           # or inside <a> element text contents.
                  )                # End recognized pre-linked alts.
                )                  # End negative lookahead assertion.
                [?=&+%\w.-]*       # Consume any URL (query) remainder.
                ~ix', '$1',
                $mediaUrl);
                
                $apiPublicKey = 'AIzaSyBjID4YyCdhd1nJK1R8eFIHBSkFecjY0_w';
                $checkUrl = 'https://www.googleapis.com/youtube/v3/videos?part=id&id=' . $videoId . '&key=' . $apiPublicKey;
                $check = json_decode(file_get_contents($checkUrl));
                $thumb1 = 'http://img.youtube.com/vi/'.$videoId.'/1.jpg';
                $thumb2 = 'http://img.youtube.com/vi/'.$videoId.'/2.jpg';
                $thumb3 = 'http://img.youtube.com/vi/'.$videoId.'/3.jpg';
                $default= 'http://img.youtube.com/vi/'.$videoId.'/default.jpg';
                $large  = 'http://img.youtube.com/vi/'.$videoId.'/0.jpg';
                $hd     = 'http://img.youtube.com/vi/'.$videoId.'/hqdefault.jpg';
                $hr     = 'http://img.youtube.com/vi/'.$videoId.'/maxresdefault.jpg';

                if (count($check->items) > 0) 
                {
                    return (object)array(
                        'url' => $mediaUrl,
                        'key' => $videoId,
                        'thumb1' => $thumb1,
                        'thumb2' => $thumb2,
                        'thumb3' => $thumb3,
                        'default' => $default,
                        'large' => $large,
                        'hd' => $hd,
                        'hr' => $hr,
                    );
                }
            }
        }

         // Assume some general media source
        return false;
    }
}
