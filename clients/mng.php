<?php
namespace Tulparstudyo\Kargo\Clients;

class Mng extends  Base{

    public function hesap_test($data){
        $result = $this->new_result();
        $service_url ="http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx?WSDL";
        $send = array('pMusteriSiparisNo'=>'1', 'pKullaniciAdi'=>$data['user_name'], 'pSifre'=>$data['user_pass'] );
        $response = $this->getSOAP($service_url, 'MusteriSiparisIptal', $send );
        if(isset($response['MusteriSiparisIptalResult']) ){
            if(isset($response['pWsError'])){
                if(strpos($response['pWsError'], 'E00')===false){
                    $result['status'] = 1;
                }
            } else{
                $result['status'] = 1;
            }
        }
        if($result['status']==1){
            $result['html'] .= '<ul>';
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
            $result['html'] .= '</ul>';
        }
        return $result;
    }
    public function kayit_ac($data){
        $result = $this->new_result();
        $service_url = 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx?WSDL';
        if(defined('TEKNOKARGO_TEST')){
            $data['user_name']  = '35615719';
            $data['user_pass'] = '356TST2425XGHPRFTG';
            $service_url = 'http://service.mngkargo.com.tr/tservis/musterikargosiparis.asmx?WSDL';
        }

        $send = array(
            "pKullaniciAdi" => $data['user_name'],
            "pSifre" => $data['user_pass'],
            "pFlKapidaOdeme" => 0,
            "pChVergiNummngi" => "",
            "pChVergiDairesi" => "",
            "pChEmail" => "",
            "pChFax" => "",
            "pChTelIs" => "",
            "pChTelCep" => $data['tel'],
            "pChTelEv" => "", "pChSokak" => "",
            "pChCadde" => "",
            "pChMeydanBulvar" => "",
            "pChMahalle" => "",
            "pChSemt" => "",
            "pChAdres" => $data['address'],
            "pChIlce" => $data['city'],
            "pChIl" => $data['zone'],
            "pFlAdresFarkli" => "0",
            "pLuOdemeSekli" => "P",
            "pChSiparisNo" => $data['barcode'],
            "pAliciMusteriAdi" => html_entity_decode($data['name'], ENT_COMPAT, "UTF-8"),
            "pAliciMusteriBayiNo" => "",
            "pAliciMusteriMngNo" => "",
            "pKargoParcaList" => "1:1:1:URUN:1:;",
            "pFlGnSms" => 0,
            "pFlAlSms" => 0,
            "pChIcerik" => "Online Satış",
            "pChIrsaliyeNo" =>  $data['barcode'],
            "pPrKiymet" => 0,
            "pChBarkod" => $data['barcode']
        );

        if($data['kargo_method'] == 'codcash_normal' )
        {
            $send["pFlKapidaOdeme"]	=  1;
            $send["pLuOdemeSekli"]	= "P";
            $send["pPrKiymet"]		=  number_format((float)$data['total'],2,',','');//tahsilatlı teslimat
        } else if($data['kargo_method'] == 'codcc_normal') {
            $send["pFlKapidaOdeme"]	=  1;
            $send["pLuOdemeSekli"]	= "P";
            $send["pPrKiymet"]		=   number_format((float)$data['total'],2,',','');//tahsilatlı teslimat
        }
        error_reporting(0);
        @ini_set('display_errors', 0);
        $result['status'] = 0;
        $result['message'] = 'İşlem Yapılamadı';
        $result['data']['kargo_method'] = $data['kargo_method'];
        $result['data']['method_name'] = $data['method_name'];
        $result['data']['order_id'] = $data['order_id'];
        $result['data']['kargo_tarih'] = date('Y-m-d H:i:s');
        $result['data']['kargo_firma'] = 'mng';
        $result['data']['kargo_barcode'] = $data['barcode'];
        $result['data']['kargo_talepno'] = '';
        $result['data']['order_status_id'] = $data['order_status_id'];
        try{
            $response = $this->getSOAP($service_url, 'SiparisGirisiDetayliV2', $send );
            if(isset($response['SiparisGirisiDetayliV2Result'])){
                if($response['SiparisGirisiDetayliV2Result']==1)
                {
                    $result['status'] = 1;
                    $result['message'] = "MNG Kargo ".$data['method_name'].' Kargo Kaydı Açıldı';
                    $result['data']['kargo_talepno'] =  '';
                } else {
                    $result['status'] = 0;
                    $result['message'] = $response['SiparisGirisiDetayliV2Result'];
                }
            } else {
                $result['status'] = 0;
                $result['message'] = 'Geçersiz servis sonucu.';
            }

        } catch(Exception $e) {
            $result['status'] = 0;
            $result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
        }
        return $result;
    }
    public function kargo_durumu_al($data){
        $result = $this->new_result();
        $result['status'] = 0;
        $result['data'] = $data;

        error_reporting(0);
        @ini_set('display_errors', 0);
        try{
            $service_url = 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx?wsdl';
            $send = array('pMusteriNo'=>$data['user_name'], 'pSifre'=>$data['user_pass'],  'pSiparisNo'=>$data['barcode']);
            $response = $this->getSOAP($service_url, 'KargoBilgileriByReferans', $send );

            $xml = simplexml_load_string($response['KargoBilgileriByReferansResult']['any']);
            $json = json_encode($xml);
            $soap_result = json_decode($json,TRUE);
            if(isset($soap_result['NewDataSet']) && isset( $soap_result['NewDataSet']['Table1']))
            {
                $result['status'] = 1;
                $detail =  $soap_result['NewDataSet']['Table1'];
                if(isset($detail['KARGO_STATU']))
                {
                    $result['data']['kargo_sonuc'] = $detail['KARGO_STATU_ACIKLAMA'];
                    $result['message'] = $detail['KARGO_STATU_ACIKLAMA'];
                    //$kargo_link = '';
                    if(isset($detail['KARGO_TAKIP_URL']))
                    {
                        $result['data']['kargo_url']  = $detail['KARGO_TAKIP_URL'];
                    }
                }
            } else {

                $result['message'] = "#api Hatası: ".$response->ShippingDeliveryVO->outResult;
            }

        } catch(Exception $e) {
            $result['message'] = 'Kargo Servislerine Bağlanılamadı. Lütfen tekrar deneyiniz. <br>'.$e->getMessage();
        }
        return $result;
    }

}
