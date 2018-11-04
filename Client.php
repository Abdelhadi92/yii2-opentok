<?php
namespace abushamleh\opentok;

use yii\base\Component;
use OpenTok\OpenTok;



class Client extends Component
{
    public $api_key;
    public $api_secret;
    public $opentok;

    public function init()
    {
        $this->opentok = new OpenTok($this->api_key, $this->api_secret);;
    }


    public function __call($name, $params)
    {
        if(method_exists($this->opentok, $name)){
            return call_user_func_array([$this->opentok, $name], $params);
        }
        parent::call($name, $params); // We do this so we don't have to implement the exceptions ourselves
    }
}
