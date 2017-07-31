<?php

namespace zedsh\laravel\B24;

use Illuminate\Support\Facades\Auth as NativeAuth;

class Auth
{
   	public $client;

    public function __construct()
		{
    $this->client = new \GuzzleHttp\Client();
    }

    public function refresh_user()
		{
		 if(!NativeAuth::guest() && method_exists(NativeAuth::user(),'B24Refresh')) NativeAuth::user()->B24Refresh();
		}
		
		public function check_access()
    { 
    $name='methods';
		$response=$this->client->request('GET',env('B24_HOSTNAME').'/rest/'.$name.'.json',['query'=>['auth'=>session('b24_credentials')->access_token]]);
		return ($response->getStatusCode()==200); 
    }
		
		public function process_response($response)
    {
      if($response->getStatusCode()==200){
      $cred=json_decode($response->getBody());
      $cred->expires_at = time() + $cred->expires_in;
      session()->put('b24_credentials', $cred);
      return true;
      }

    return false;
    }

    public function step1(){
    return redirect(env('B24_HOSTNAME').'/oauth/authorize/?client_id='.env('B24_CLIENT_ID'));
    }

    public function step2($request){
    $response=$this->client->request('GET',
        "https://oauth.bitrix.info/oauth/token/",
        ['query'=>[
        'grant_type'=>'authorization_code',
        'client_id'=>env('B24_CLIENT_ID'),
        'client_secret'=>env('B24_CLIENT_SECRET'),
        'code'=>$request->code
        ],
        'http_errors' => false,
        ]);

    if(!$this->process_response($response)) return $this->step1();


   	$this->refresh_user();
    return redirect(\URL::to('/'));
    }

    public function toket_refresh()
    {
    $response=$this->client->request('GET',
    "https://oauth.bitrix.info/oauth/token/",
    [
    'http_errors' => false,
      'query'=>[
      'client_id'=>env('B24_CLIENT_ID'),
      'client_secret'=>env('B24_CLIENT_SECRET'),
      'refresh_token'=>session('b24_credentials'),
      ],
    ]
    );

    if(!$this->process_response($response)) return $this->step1();

    return $next($request);
    }

		
		
		public function handle($request, \Closure $next,$step='two')
    {

        if ($request->has(['code','domain','member_id'])) {
        return $this->step2($request);
        }

      	if ($step=='init' && !$request->session()->has('b24_credentials')) {
        return $this->step1();
        }

        if ($step=='init' && $request->session()->has('b24_credentials') && time() > $request->session()->get('b24_credentials')->expires_at){
        return $this->toket_refresh();
        }

				if($step=='init' && !$this->check_access()) return $this->step1();


	 return $next($request);
   }

}
?>
