<?php
namespace Tulparstudyo\Kargo\Clients;

class Yurtici extends  Base{
	public function hesap_test($data){
        $result = $this->new_result();

        $service_url ="http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl";
        $host = 'webservices.yurticikargo.com';
        $port = 8080;
        $connection = @fsockopen($host, $port);
        $result['status']=0;
        $result['html'] = '<ul>';
        $data['barkodlar'] = array('1');

        if (is_resource($connection))
        {
            fclose($connection);
            $result = $this->kayit_iptal($data);
            $result['html'] = '<ul>';
        } else {
            $result['html'] = '<ul>';
            $result['html'] .= '<li>'.$host . ':' . $port . ' is not responding</li>';
        }
        if($result['status']==1){
            $result['html'] .= '<li><strong class="text-success">Başarılı</strong></li>';
            $result['html'] .= '<li>Servis: '.$service_url.'</li>';
            $result['html'] .= '<li>Kullanıcı Adı: '.$data['user_name'].'</li>';
            $result['html'] .= '<li>Şifre: '.$data['user_pass'].'</li>';
            $result['html'] .= '</ul>';
        } else{
            $result['html'] .= '<ul>';
            $result['html'] .= '<li><strong class="text-danger">Başarısız</strong></li>';
            $result['html'] .= '<li>Servis: '.$service_url.'</li>';
            $result['html'] .= '<li>Kullanıcı Adı: '.$data['user_name'].'</li>';
            $result['html'] .= '<li>Şifre: '.$data['user_pass'].'</li>';
        }
        $result['html'] .= '</ul>';
        return $result;
    }

    public function kayit_ac($data){
        $result = $this->new_result();
        $service_url = 'http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl';
		$order = array(
			'cargoCount'		=>$data['paket'],
			'cargoKey'			=>$data['barcode'],
			'cityName'			=>$data['zone'],
			'dcCreditRule'		=>'',
			'dcSelectedCredit'	=>'',
			'invoiceKey'		=>$data['barcode'],
			'receiverAddress'	=>$data['address'],
			'receiverCustName'	=>$data['name'],
			'receiverPhone1'	=>$data['tel'],
			'townName'			=>$data['city'],
			'ttDocumentId'		=>'',
			'waybillNo'			=>$data['barcode'],
			'taxOfficeId'		=>'',
			'receiverPhone1'	=>$data['tel'],
			'receiverPhone2'	=>$data['tel'],
		);
        //print_r($data);
		if($data['kargo_method'] == 'codcash_normal' )
		{
			$order['ttDocumentId']       = $data['barcode'];
			$order['ttCollectionType']   = '0';
			$order['ttInvoiceAmount']    = (float)$data['total'];
			/*
            if($data['kargo_odemesi']=='musteri_oder'){
                $order['ttDocumentSaveType']   ='1';
                $order['kg']   = $data['kargo_agirlik'];
                $user_name  = 'AÖTc';
                $user_pass = 'AÖTc';
            }
            */
		} else if($data['kargo_method'] == 'codcc_normal') {
			$order['ttDocumentId']       = $data['barcode'];
			$order['ttCollectionType']   = '1';
			$order['dcSelectedCredit']   = '1';
			$order['ttInvoiceAmount']    = (float)$data['total'];
			/*
            if($data['kargo_odemesi']=='musteri_oder'){
                $order['ttDocumentSaveType']   ='1';
                $order['kg']   = $data['kargo_agirlik'];
                $user_name  = 'AÖTkredi';
                $user_pass = 'AÖTkredi';
            }
            */
		}
		error_reporting(0);
		@ini_set('display_errors', 0);
        $result = $this->new_result();

        $result['message'] = 'İşlem Yapılamadı';
		$result['data']['kargo_method'] = $data['kargo_method'];
		$result['data']['method_name'] = $data['method_name'];
		$result['data']['order_id'] = $data['order_id'];
		$result['data']['kargo_tarih'] = date('Y-m-d H:i:s');
		$result['data']['kargo_firma'] = 'yurtici';
		$result['data']['kargo_barcode'] = $data['barcode'];
		$result['data']['kargo_talepno'] = '';
		$result['data']['order_status_id'] = $data['order_status_id'];
		try{
			$send		= array('wsUserName'=>$data['user_name'], 'wsPassword'=>$data['user_pass'], 'userLanguage'=>'TR', 'ShippingOrderVO'=>array($order));
            $response = $this->getSOAP($service_url, 'createShipment', $send );
			if(isset($response['ShippingOrderResultVO']) && $response['ShippingOrderResultVO']['outFlag']==0)
			{
				$result['status'] = 1;
				$result['message'] = "Yurtiçi Kargo ".$data['method_name'].' Kargo Kaydı Açıldı';
				$result['data']['kargo_talepno'] =  $response['ShippingOrderResultVO']['jobId'];
			} else {
				$result['status'] = 0;
				$result['message'] = $response['ShippingOrderResultVO']['outResult'];
				if(isset($response['ShippingOrderResultVO']))
				{
					$result['message'] .= $this->method[$data['kargo_method']]['method_name'].': '.$response['ShippingOrderResultVO']['shippingOrderDetailVO']['errMessage'];
				}
			}
		} catch(Exception $e) {
            $result['status'] = 0;
			$result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
		}
		return $result;
    }

    public function kayit_iptal($data){
        $result = $this->new_result();
        $cargoKeys = isset($data['barkodlar'])?$data['barkodlar']:array();
        $service_url ="http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl";
        $send = array('cargoKeys'=>$cargoKeys, 'wsUserName'=>$data['user_name'], 'wsPassword'=>$data['user_pass'], 'userLanguage'=>'TR' );
        $response = $this->getSOAP($service_url, 'cancelShipment', $send );

        if(isset($response['ShippingOrderResultVO'])  ){
            if(isset($response['ShippingOrderResultVO']['outFlag']) && $response['ShippingOrderResultVO']['outFlag']==0){
                $result['status'] = 1;
                if(isset($response['ShippingOrderResultVO']['shippingCancelDetailVO']['errMessage'])){
                    $result['message'] = $response['ShippingOrderResultVO']['shippingCancelDetailVO']['errMessage'];
                } elseif(isset($response['ShippingOrderResultVO']['shippingCancelDetailVO']['operationMessage'])){
                    $result['message'] = $response['ShippingOrderResultVO']['shippingCancelDetailVO']['operationMessage'];
                }
            } else{
                $result['message'] = $response['ShippingOrderResultVO']['outResult'];
            }
        } else{
            $result['message'] = "Geçersiz webservis sonucu";
        }
        return $result;

    }

    public function takip_linki_al($data){

    }

    public function takip_kodu_al($data){

    }

    public function kargo_durumu_al($data){
        $result = $this->new_result();
        error_reporting(0);
        @ini_set('display_errors', 0);
        try{
            $service_url = 'http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl';
            $send		= array('wsUserName'=>$data['user_name'], 'wsPassword'=>$data['user_pass'], 'wsLanguage'=>'TR', 'keys'=>array($data['barcode']),'keyType'=>0, 'addHistoricalData'=>0,'onlyTracking'=>1);
            $response = $this->getSOAP($service_url, 'queryShipment', $send );
            if(isset($response['ShippingDeliveryVO']) && $response['ShippingDeliveryVO']['outFlag']==0)
            {
                if($response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['operationCode'])
                {
                    $result['data']['kargo_url'] = $response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['shippingDeliveryItemDetailVO']['trackingUrl'];
                }
                $result['data']['kargo_sonuc'] = $response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['operationMessage'];
                $result['message'] = $result['data']['kargo_sonuc'] ;
            }
            if(isset($response['ShippingDeliveryVO']) && isset($response['ShippingDeliveryVO']['shippingDeliveryDetailVO']) && isset($response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['errMessage'] ))
            {
                $result['data']['kargo_sonuc'] = $response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['errMessage'];
                $result['message'] = $response['ShippingDeliveryVO']['shippingDeliveryDetailVO']['errMessage'] ;
            }
        } catch(Exception $e) {
            $result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
        }
        return $result;
    }
    /*
    private function getSOAP($service_url, $service_method,  $send ){
        $result['message'] = '';
        $send['userLanguage'] = 'TR';
 		if (!extension_loaded('soap')) {
			throw new Exception('SOAP requests is unavailable');
		}
		try{
			$client 	= new SoapClient($service_url, array('trace' => 1, 'exceptions' => 1));
			$response 	= $client->$service_method( $send );
            return json_decode(json_encode($response),1);
		} catch(Exception $e) {
			$result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
		}

    }
    */
}
?>
