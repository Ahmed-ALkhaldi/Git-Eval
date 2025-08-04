<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{

    //Show home page
    public function index(Request $request){

        $books = Book::orderBy('created_at','DESC');

        if (!empty($request->keyword)) {
            $books->where('title','like','%'.$request->keyword.'%');
        }
        $books = $books->where('status',1)->paginate(8);


        return view('home',[
            'books' => $books
        ]);
    }


    //show book details page
    public function detail($id){

        $book = Book::with(['reviews.user','reviews' => function($query){
            $query->where('status',1);
        }])->findOrFail($id);

        if($book->status == 0){
            abort(404);
        }

        $relatedBooks = Book::where('status',1)->take(3)->where('id','!=',$id)->inRandomOrder()->get();

        return view('book-detail',[
            'book' => $book,
            'relatedBooks' => $relatedBooks
        ]);
    }

    //Save review in DB
    public function saveReview(Request $request){
        // dd($request->collect());
        $validator = Validator::make($request->all(),[
            'review0' => 'required|min:10',
            'rating' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Apply condition here

        $countReview = Review::where('user_id',Auth::user()->id)->where('book_id',$request->book_id)->count();

        if ($countReview > 0) {
            session()->flash('error','You already submitted a review.');
            return response()->json([
                'status' => true,
            ]);
        }
        $review0 = new Review();
        $review0->review0 = $request->review0;
        $review0->rating = $request->rating;
        $review0->user_id = Auth::user()->id;
        $review0->book_id = $request->book_id;
        $review0->save();

        session()->flash('success','Review submitted successfully.');
        // return response()->json([
        //         'status' => true
        // ]);
        return redirect()->route('book.detail',$request->book_id)->with('success','Review submitted successfully.');
    }

    //Show reviews
    // public function
}
