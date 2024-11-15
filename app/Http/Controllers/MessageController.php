<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index()
    {
        // Get images from the database
        $images = Message::all();
        return view('index', compact('images'));
    }

    public function uploadImage(Request $request)
    {
        // Validate the incoming request for the image
        $request->validate([
            'image' => 'required|string',
        ]);

        // Extract base64 image data
        $imageData = $request->image;
        $imageParts = explode(";base64,", $imageData);
        if (count($imageParts) < 2) {
            return response()->json(['error' => 'Invalid base64 data'], 400);
        }

        $imageBase64 = base64_decode($imageParts[1]);
        if ($imageBase64 === false) {
            return response()->json(['error' => 'Invalid image encoding'], 400);
        }

        // Generate a unique file name for the image
        $fileName = uniqid() . '.png';

        // Save the image to the 'uploads' directory using Laravel's Storage system
        $filePath = 'uploads/' . $fileName;
        Storage::disk('public')->put($filePath, $imageBase64);

        // Save the file path in the database
        Message::create(['image' => $filePath]);

        return response()->json(['success' => true, 'file' => $fileName]);
    }
}
