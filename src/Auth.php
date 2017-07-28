<?php

namespace zedsh\laravel\B24;

class Auth
{
   	public $client;

    public function __construct()
		{
    $this->client = new \GuzzleHttp\Client();
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


    return redirect(route('transactions'));
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

    public function handle($request, \Closure $next)
    {

        if ($request->has(['code','domain','member_id'])) {
        return $this->step2($request);
        }

      if (!$request->session()->has('b24_credentials')) {
        return $this->step1();
        }

        if (time() > $request->session()->get('b24_credentials')->expires_at){
        return $this->toket_refresh();
        }

   return $next($request);
   }

}
?>
