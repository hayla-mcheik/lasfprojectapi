<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DestinationController extends Controller
{
    public function index() { return Destination::latest()->get(); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'=>'required',
            'description'=>'nullable',
            'image'=>'nullable|image'
        ]);

        if ($r->hasFile('image')) {
            $data['image'] = $r->file('image')->store('destinations','public');
        }

        $data['slug'] = Str::slug($data['name']);
        return Destination::create($data);
    }

    public function update(Request $r, Destination $destination)
    {
        if ($r->hasFile('image')) {
            $destination->image = $r->file('image')->store('destinations','public');
        }

        $destination->update($r->only('name','description'));
        return $destination;
    }

    public function destroy(Destination $destination)
    {
        $destination->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
