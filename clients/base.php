<?php
namespace Tulparstudyo\Kargo\Clients;

class Base {
    public $debug = [];

    public function new_result(){
        $result[ 'status' ] = 0;
        $result[ 'code' ] = "";
        $result[ 'message' ] = "";
        $result[ 'html' ] = "";
        return $result;
    }
    public function getSOAP($service_url, $service_method,  $send ){

        if (!extension_loaded('soap')) {
            defined('TEKNOKARGO_DEBUG') or define('TEKNOKARGO_DEBUG', true);
            $this->debug['soap_extension'] = "SOAP requests is unavailable";
        }
        try{
            if(defined('TEKNOKARGO_DEBUG')){
                $this->debug['endpoint'] = "Servis Endpoint: $service_url";
                $this->debug['send'] = "send:<pre>".print_r($send, 1)."</pre>";
            }
            $client = new \SoapClient($service_url, array('trace' => 1, 'exceptions' => 1));
            $response 	= $client->$service_method( $send );
            if(defined('TEKNOKARGO_DEBUG')){
                $this->debug['response'] =   "response:<pre>".print_r($response, 1)."</pre>";
            }
            return json_decode(json_encode($response),1);
        } catch (\SoapFault $e) {
            if(defined('TEKNOKARGO_DEBUG')){
                $this->debug['exception'] = $e->getMessage();
            }
            return false;
        } catch(Exception $e) {
            if(defined('TEKNOKARGO_DEBUG')){
                $this->debug['exception'] =   $e->getMessage();
            }
            return false;
        }

    }

}