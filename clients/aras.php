<?php
namespace Tulparstudyo\Kargo\Clients;

class Aras extends Base {

    public function hesap_test($data){
        $service_url ="http://customerws.araskargo.com.tr/arascargoservice.asmx?WSDL";
        $data['barkodlar'] = array('1');
        $result = $this->kayit_iptal($data);
        if($result['status']==1){
            $result['html']  = '<ul>';
            $result['html'] .= '<li><strong class="text-success">Başarılı</strong></li>';
            $result['html'] .= '<li>Servis: '.$service_url.'</li>';
            $result['html'] .= '<li>Kullanıcı Adı: '.$data['user_name'].'</li>';
            $result['html'] .= '<li>Şifre: '.$data['user_pass'].'</li>';
            $result['html'] .= '</ul>';
        } else {
            $result['html'] = '<ul>';
            $result['html'] .= '<li><strong class="text-danger">Başarısız</strong></li>';
            $result['html'] .= '<li>Servis: '.$service_url.'</li>';
            $result['html'] .= '<li>Kullanıcı Adı: '.$data['user_name'].'</li>';
            $result['html'] .= '<li>Şifre: '.$data['user_pass'].'</li>';
            $result['html'] .= '</ul>';
        }
        return $result;
    }
    public function kayit_ac($data){
        $result = $this->new_result();
        $user_name  = $data['user_name'];
        $user_pass = $data['user_pass'];
        $service_url = 'http://customerws.araskargo.com.tr/arascargoservice.asmx?WSDL';
        if(defined('TEKNOKARGO_TEST')){
            $user_name  = 'neodyum';
            $user_pass = 'nd2580';
            $service_url = 'http://customerservicestest.araskargo.com.tr/arascargoservice/arascargoservice.asmx?WSDL';
        }
        $order = array(
            "UserName"              => $user_name,
            "Password"              => $user_pass,
            "TradingWaybillNumber"  => $data['barcode'],
            "InvoiceNumber"  		=> $data['order_id'],
            "IntegrationCode"      	=> $data['barcode'],
            "ReceiverName"			=> $data['name'],
            "ReceiverAddress"       => $data['address'],
            "ReceiverCityName"      => $data['zone'],
            "ReceiverTownName"      => $data['city'],
            "ReceiverPhone1"        => $data['tel'],
            "PayorTypeCode"      	=> 1,
            "IsCod"            		=> 0, //'Tahsilatlı (0=Hayır, 1=Evet)
            "PieceCount"      		=> $data['paket'],
            "PieceDetails"			=> array( array("VolumetricWeight"=>"2",
                "Weight"=>"2",
                "BarcodeNumber"=>$data['barcode'],
                "ProductNumber"=>$data['order_id'],
                "Description"=>'Online Satış'
            )
            ),
            "IsWorldWide"      		=> 0,// (0=Yurtiçi, 1=Yurtdışı)
        );

        if($data['kargo_method'] == 'codcash_normal' )
        {
            $order['IsCod']              =1;
            $order['CodCollectionType']  = 0; //0-Nakit,1-Kredi Kartı
            $order['CodAmount']    =(float)$data['total'];
        } else if($data['kargo_method'] == 'codcc_normal') {
            $order['IsCod']              =1;
            $order['CodCollectionType']  = 1; //0-Nakit,1-Kredi Kartı
            $order['CodAmount']    =(float)$data['total'];
        }
        error_reporting(0);
        @ini_set('display_errors', 0);
        $result['status'] = 0;
        $result['message'] = 'İşlem Yapılamadı';
        $result['data']['kargo_method'] = $data['kargo_method'];
        $result['data']['order_id'] = $data['order_id'];
        $result['data']['kargo_tarih'] = date('Y-m-d H:i:s');
        $result['data']['kargo_firma'] = 'aras';
        $result['data']['kargo_barcode'] = $data['barcode'];
        $result['data']['kargo_talepno'] = '';
        $result['data']['order_status_id'] = $data['order_status_id'];
        try{
            $send['orderInfo']['Order'] = array($order);
            $send['userName'] = $user_name;
            $send['password'] = $user_pass;

            $response = $this->getSOAP($service_url, 'setOrder', $send );
            if(isset($response['SetOrderResult']) )
            {
                if(isset($response['SetOrderResult']['OrderResultInfo'])){
                    if($response['SetOrderResult']['OrderResultInfo']['ResultCode']==0){
                        $result['status'] = 1;
                        $result['message'] = $data['method_name'].' Kargo Kaydı Açıldı';
                        $result['data']['kargo_url'] = '';
                    } else{
                        $result['status'] = 0;
                        $result['message'] = $response['SetOrderResult']['OrderResultInfo']['ResultMessage'];

                    }

                } else{
                    $result['status'] = 0;
                    $result['message'] .= '102';
                }
                $result['data']['kargo_talepno'] =  $response['ShippingOrderResultVO']['jobId'];
            } else {
                $result['status'] = 0;
                $result['message'] .= '102';
            }
        } catch(Exception $e) {
            $result['status'] = 0;
            $result['message'] .= 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
        }
        return $result;
    }

    public function kayit_iptal($data){
        $result = $this->new_result();
        $result['status'] = 0;
        $user_name  = $data['user_name'];
        $user_pass = $data['user_pass'];
        $integrationCode = isset($data['barkodlar'])?current($data['barkodlar']):'';
        $service_url ="http://customerws.araskargo.com.tr/arascargoservice.asmx?WSDL";
        $send = array('integrationCode'=>$integrationCode, 'userName'=>$user_name, 'password'=>$user_pass );
        $response = $this->getSOAP($service_url, 'CancelDispatch', $send );
        if(isset($response['CancelDispatchResult']) && $response['CancelDispatchResult']['ResultCode']>=-1 ){
            $result['status'] = 1;
            if(isset($response['CancelDispatchResult']['ResultMessage'])){
                $result['message'] = $response['CancelDispatchResult']['ResultMessage'];
            } else{
                $result['message'] = '#101';
            }
        } else{
            //$result['html'] = "<textarea>".json_encode($response)."</textarea>"."<textarea>".json_encode($response)."</textarea>";
        }
        return $result;
    }

    public function takip_linki_al($data){

    }

    public function takip_kodu_al($data){

    }

    public function kargo_durumu_al($data){
        $result = $this->new_result();
        $result['status'] = 0;
        $result['data'] = $data;

        $takip_user  = $data['takip_user'] ;
        $takip_pass = $data['takip_pass'] ;
        $user_code = $data['user_code'];
        $IntegrationCode = $data['barcode'];
        error_reporting(0);
        @ini_set('display_errors', 0);
        try{
            $service_url = 'http://customerservices.araskargo.com.tr/ArasCargoCustomerIntegrationService/ArasCargoIntegrationService.svc?wsdl';
            $loginInfo = '<LoginInfo>
                <UserName>'.$takip_user.'</UserName>
                <Password>'.$takip_pass .'</Password>
                <CustomerCode>'.$user_code.'</CustomerCode>
                </LoginInfo>';
            $queryInfo = "<QueryInfo>
                <QueryType>1</QueryType>
                <IntegrationCode>$IntegrationCode</IntegrationCode>
                </QueryInfo>";
            $send = array('loginInfo'=>$loginInfo , 'queryInfo'=>$queryInfo);
            if(isset($_GET['debug'])) print_r($send);
            $response = $this->getSOAP($service_url, 'GetQueryXML', $send );
            if(isset($_GET['debug'])) print_r($response);
            if(isset($response['GetQueryXMLResult']))
            {
                $xml = simplexml_load_string($response['GetQueryXMLResult']);
                $xml = json_encode($xml);
                $xml = json_decode($xml, true);
                if( isset($xml['Cargo']) ){
                    if( !isset($xml['Cargo']['DURUMU'])){
                        $xml['Cargo'] = current($xml['Cargo']);
                    }
                    $result['data']['kargo_sonuc'] = $xml['Cargo']['DURUMU'];
                    $kargo_takipno = isset($xml['Cargo']['KARGO_TAKIP_NO'])?$xml['Cargo']['KARGO_TAKIP_NO']:'';
                    if($kargo_takipno)
                    {
                        $result['data']['kargo_url'] = 'http://kargotakip.araskargo.com.tr/mainpage.aspx?code='.$kargo_takipno;
                    }
                }
            }
            $result['message'] = "Durumu: ".$result['data']['kargo_sonuc'];
        } catch(Exception $e) {
            $result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
        }
        return $result;

    }
    /*
    private function getSOAP($service_url, $service_method,  $send ){

        $result['message'] = '';
 		if (!extension_loaded('soap')) {
			throw new Exception('SOAP requests is unavailable');
		}
		try{
            if(defined('TEKNOKARGO_DEBUG')){
                $result['message'] .= "<br>Servis Endpoint: $service_url<br>\r\n";
                $result['message'] .= "<br>Request data:<br>\r\n";
                $result['message'] .= print_r($send, 1);
            }
			$client 	= new SoapClient($service_url, array('trace' => 1, 'exceptions' => 1));
			$response 	= $client->$service_method( $send );
            if(defined('TEKNOKARGO_DEBUG')){
                $result['message'] .= "<br>Response data:<br>\r\n";
                $result['message'] .= print_r($response, 1);
            }
            return json_decode(json_encode($response),1);
		} catch(Exception $e) {
            if(defined('TEKNOKARGO_DEBUG')){
                $result['message'] .= "<br>Exception data:<br>\r\n";
                $result['message'] .= print_r($e->getMessage(), 1);
            }
			$result['message'] .= 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
		}

    }
    */
}
?>
