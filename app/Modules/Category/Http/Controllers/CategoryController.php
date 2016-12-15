<?php

namespace App\Modules\Category\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Category\Model\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category = Category::all();
        $meta['message'] = 'List all category';
        $meta['status']  = true;
        $meta['code']    = 200;
        $code            = 200;
        $data            = $category;
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
                'name' => 'required|string',
                'admin_id' => 'required|integer',
                'parent_id' => 'integer'
        ]);
        if ($validator->passes()) {
            $name      = $request->name;
            $admin_id  = $request->admin_id;
            $parent_id = $request->input('parent_id',0);

            try 
            {
                $category = new Category;
                $category->name         = $name;
                $category->parent_id    = $parent_id;
                $category->admin_id     = $admin_id;
                if ($category->save()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success creating category";  
                    $data = $category;  
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
                'admin_id' => 'required|integer'
        ]);
        if ($validator->passes()) {
            $id        = $request->id;
            $name      = $request->name;
            $admin_id  = $request->admin_id;
            $parent_id = $request->input('parent_id',0);

            try 
            {
                if (is_numeric($id)) {
                    $category = Category::findOrFail($id);
                    $meta['status'] = true;
                    $meta['message'] = "Success showing category ID #".$id;
                    $data = $category;       
                } else if (is_string($id)) {
                    $category = Category::where('slug',$id)->first();
                    $meta['status'] = true;
                    $meta['message'] = "Success showing category";
                    $data = $category;
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
                'id'        =>  'required|integer',
                'name'      =>  'required|string',
                'admin_id'  =>  'required|integer',
                'parent_id' =>  'integer'
        ]);
        if ($validator->passes()) {
            $id        = $request->id;
            $name      = $request->name;
            $admin_id  = $request->admin_id;
            $parent_id = $request->input('parent_id',0);

            try 
            {
                $category = Category::findOrFail($id);
                $category->name         = $name;
                $category->parent_id    = $parent_id;
                $category->admin_id     = $admin_id;
                if ($category->save()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success updating category";   
                    $data = $category;
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
                'admin_id' => 'required|integer'
        ]);
        if ($validator->passes()) {
            $id       = $request->id;
            $admin_id = $request->admin_id;

            try 
            {
                $category = Category::findOrFail($id);
                if ($category->delete()) {
                    $meta['status'] = true;
                    $meta['message'] = "Success deleting category"; 
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
            $meta['message'] = "Failed delete category";
            $meta['error'] = $validator->errors();
            $data = null;
        }
        
        $meta['code'] = 200;
        $code = 200;
        return response()->json(compact('meta','data'),$code);
        // return $this->response->array(compact('meta','data'))->setStatusCode($code);
    }
}
