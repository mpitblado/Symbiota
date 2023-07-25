<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller{
	/**
	 * Statistic controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	public function showAllCollections(Request $request){
		$this->validate($request, [
			'limit' => ['integer', 'max:1000'],
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',1000);
		$offset = $request->input('offset',0);


		$eor = false;
		$retObj = [
			"offset" => (int)$offset,
			"limit" => (int)$limit,
			"endOfRecords" => $eor,
			"count" => $fullCnt,
			"results" => $result
		];
		return response()->json($retObj);
	}

}