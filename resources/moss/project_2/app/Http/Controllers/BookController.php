<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class BookController extends Controller
{
    //Show books listing page 
    public function index(Request $request){

        $books = Book::orderBy('created_at','DESC');
        if(!empty($request->keyword)){
            $books->where('title','like','%'.$request->keyword.'%');
        }

        $books = $books->paginate(10);
        return view('books.list',[
            'books' => $books
        ]);
    }
    
    //Show create book page
    public function create(){
        return view('books.create');
    }

    //Store book in database
    public function store(Request $request){

        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
            'image' => 'nullable|image'
        ];

        if(!empty($request->image)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->fails()) {
            return redirect()->route('books.create')->withInput()->withErrors($validator);
        }

        //save book in DB
        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;

        //upload image book
        if(!empty($request->image)){
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time().'.'.$ext;
            $image->move(public_path('uploads/books'),$imageName);

            $book->image = $imageName;
            $book->save();

            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/'.$imageName)); 
            
            $img->resize(150, 150);
            $img->save(public_path('uploads/books/thumb/'.$imageName));
        }
        $book->save();

        return redirect()->route('books.index')->with('success', 'book added successfully.');
    }

    //Show edit book page
    public function edit($id){
        $book = Book::findOrFail($id);
        return view('books.edit',[
            'book' => $book
        ]);
    }

    //Update  book 
    public function update($id,Request $request){

        $book = Book::findOrFail($id);

        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ];

        if(!empty($request->iamge)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->fails()) {
            return redirect()->route('books.edit',$book->id)->withInput()->withErrors($validator);
        }

        //update book in DB
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        //upload image book
        if(!empty($request->image)){

            //this will delete old book image from books directory
            File::delete(public_path('uploads/books/'.$book->image));
            File::delete(public_path('uploads/books/thumb/'.$book->image));

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time().'.'.$ext;
            $image->move(public_path('uploads/books'),$imageName);

            $book->image = $imageName;
            $book->save();

            //Generate Image Thumbnail here
            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/'.$imageName)); 
            $img->resize(990);
            $img->save(public_path('uploads/books/thumb/'.$imageName));
        }
        return redirect()->route('books.index')->with('success', 'Book updated successfully.');
    }

    //Delete a book from database
    public function destroy(Request $request){

        $book = Book::find($request->id);

        if ($book == null) {

            session()->flash('error','Book not Found');
            return response()->json([
                'status' => false,
                'message' => 'Book not Found'
            ]);
        } else{
            File::delete(public_path('uploads/books/'.$book->image));
            File::delete(public_path('uploads/books/thumb/'.$book->image));
            $book->delete();

            session()->flash('success','Book deleted successfully.');
            return response()->json([
                'status' => true,
                'message' => 'Book deleted successfully'
            ]);
        }
    }
} 
