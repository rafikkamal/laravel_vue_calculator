<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Guest;
use App\GuestHistory;

class GuestHistoryController extends Controller
{
	private $ip;

    public function getHistory(Request $request) {
    	$guest_id = $request->guest_id;
    	if(isset($guest_id)) {
    		$guest = $this->checkGuestExists($guest_id);
    		if(isset($guest)) {
    			$guest_history = GuestHistory::select('id', 'equation', 'result')->whereGuestId($guest->id)->orderBy("created_at", "desc")->get();
    		}
    	}
    	return response()->json([
    		'guest_id'	=> isset($guest) ? $guest->id : null,
    		'guest_history' => isset($guest_history) ? $guest_history : null
    	], 200);
    }

    public function getGuest(Request $request) {
    	$this->ip = $request->ip;
    	if(isset($this->ip)) {
    		$guest = $this->checkGuestExists();
    		if(!isset($guest)) {
    			$guest = $this->createGuest();
    		}
    	}
    	return response()->json([
    		'guest_id' => $guest->id
    	], 200);
    }

    private function checkGuestExists($guest_id=null) {
    	if(isset($guest_id)) {
    		$guest = Guest::find($guest_id);
    	} else {
	    	$guest = Guest::whereIp($this->ip)->first();
	    }
 		return $guest;
    }

    private function createGuest() {
    	$guest = new Guest;
    	$guest->ip = $this->ip;
    	$guest->save();
    	return $guest;
    }

    public function postHistory(Request $request) {
    	$guest_history = new GuestHistory;
    	$guest_history->equation = $request->equation;
    	$guest_history->result	 = $request->result;
    	$guest_history->guest_id = $request->guest_id;
    	$guest_history->save();
    	if($guest_history) {
    		$error = null;
    	} else {
    		$error = "Unable to create new entry";
    	}
    	return response()->json([
    		'error' => $error
    	], 200);
    }
}
