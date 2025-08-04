<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    //Show Review in Backend
    public function index(Request $request){
        $reviews = Review::with('book','user')->orderBy('created_at','DESC');
        if (!empty($request->keyword)) {
            $reviews = $reviews->where('review','like','%'.$request->keyword.'%');
        }
        $reviews = $reviews->paginate(10);
        return view('account.reviews.list',[
            'reviews' => $reviews
        ]);
    }

    //SHow edit review page
    public function edit($id){
        $review = Review::findOrFail($id);

        return view('account.reviews.edit',[
            'review' => $review
        ]);
    }

    // Update Review
    public function updateReview($id, Request $request){
        $review = Review::findOrFail($id);
        $validator = Validator::make($request->all(),[
            'review0' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.reviews.edit',$id)->withInput()->withErrors($validator);
        }

        $review->review0 = $request->review0;
        $review->status = $request->status;
        $review->save();

        session()->flash('success','Review updated successfully');
        return redirect()->route('account.reviews',$id);
    }

    //delete review from db
    public function deleteReview(Request $request){

        $id = $request->id;
        $review = Review::find($id);

        if ($review == null) {
            session()->flash('error','Review not found');
            return response()->json([
                'status' => false
            ]);
        } else {
            $review->delete();
            session()->flash('success','Review deleted successfully.');
            return response()->json([
                'status' => true
            ]);
        }
    }
}
