<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\AfricasTalking;
use Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Session;

class UserController extends Controller
{
    private $session_id,$service_code,$phone_number,$text;
    private $at_username,$at_api_key,$AT,$level,$user_response,$text_array,$screen_response;

    public function __construct()
    {
        $this->session_id   =request()->get('sessionId');
        $this->service_code =request()->get('serviceCode');
        $this->phone_number =request()->get('phoneNumber');
        $this->text         =request()->get('text');

        $this->at_api_key   =env('AT_API_KEY');
        $this->at_username  =env('AT_USERNAME');
        $this->AT           =new AfricasTalking($this->at_username,$this->at_api_key);

        $this->level= 0;
        $this->screen_response="";

        $this->text_array=explode("*",$this->text);
        $this->user_response=trim(end($this->text_array));
    }
    public function register()
    {
        $new_level=(new Session)->where('phone_number',$this->phone_number)->pluck('session_level')->first();
        if(!empty($new_level)){
            $this->level=$new_level;
        }

            switch ($this->level){
                case 0:
                    switch ($this->user_response){
                        case "":
                            Session::create([
                                'session_id'    =>$this->session_id,
                                'service_code'  =>$this->service_code,
                                'phone_number'  =>$this->phone_number,
                                'session_level' =>1
                            ]);
                            User::create([
                                'phone_number'  =>$this->phone_number
                            ]);
                            $this->screen_response="Register to access our services\n";
                            $this->screen_response.="1:Register\n";
                            $this->screen_response.="2:Login";

                            $this->ussd_proceed($this->screen_response);
                            break;
                    }
                    break;
                case 1:
                    switch($this->user_response){
                        case "1":
                            $this->screen_response.="Enter your first name\n";
                            $this->ussd_proceed($this->screen_response);
                            (new Session())->where("phone_number",$this->phone_number)->update(['session_level'=>2]);
                            break;
                    }
                    break;
                case 2:
                    (new User())->where("phone_number",$this->phone_number)->update(["first_name"=>$this->user_response]);
                    $this->screen_response="Enter your last name\n";
                    $this->ussd_proceed($this->screen_response);
                    (new Session())->where("phone_number",$this->phone_number)->update(['session_level'=>3]);
                    break;
                case 3:
                    (new User())->where("phone_number",$this->phone_number)->update(["last_name"=>$this->user_response]);
                    $this->screen_response="Enter your email address\n";
                    $this->ussd_proceed($this->screen_response);
                    (new Session())->where("phone_number",$this->phone_number)->update(['session_level'=>4]);
                    break;
                case 4:
                    (new User())->where("phone_number",$this->phone_number)->update(["email"=>$this->user_response]);
                    $this->screen_response="Enter your password\n";
                    $this->ussd_proceed($this->screen_response);
                    (new Session())->where("phone_number",$this->phone_number)->update(['session_level'=>5]);
                    break;
                case 5:
                    (new User())->where("phone_number",$this->phone_number)->update(["password"=>Hash::make($this->user_response)]);
                    $user=(new user())->where('phone_number',$this->phone_number)->pluck('first_name')->first();

                    $this->screen_response=
                        "Congratulations <strong><i>$user</i></strong>,you have successfully registered for JKUAT Online Classes\n";
                    $this->ussd_finish($this->screen_response);
                    break;
        }
    }
    public function ussd_proceed($proceed)
    {
        echo "CON $proceed";
    }
    public function ussd_finish($finish)
    {
        echo "END $finish";
    }
}
