<?php
//функция отправки\получение запроса
function get_curl($queryUrl,$queryData){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result, 1);
    return $result;
}

if (isset($_GET['email'])) {
//id ответственного
$ASSIGNED_BY_ID= 1;
//переменная искомого номера
$email = "factory@dolce.kz";
$PHONE= +77272719999;
//токен, получаемый при создании webhook
$token='cm4epfax52wdyvoe';
//функция bitrix24 получения списка контактов.
$json_function='crm.contact.list';
// id пользователя с админ правами (в моем случае id, который создавал webhook)
$admin_id='1';
//ссылка bitrix портала
$url = 'https://integratorprofi.bitrix24.ua';
//создаем запрос для поиска контакта по номеру телефона в базе bitrix
$query = http_build_query(array( 
    'filter'=> array("EMAIL" => $email ),
    'select' => array( "ID", "NAME", "LAST_NAME" ),
));
//выполняем запрос, получаем ответ от bitrix
$res_curl = get_curl($url.'/rest/'.$admin_id.'/'.$token.'/'.$json_function.'.json',$query);
//изменяем имя вызываемого метода
$json_function='crm.lead.add';
//проверяем, что вернул нам портал
    if (array_key_exists('result', $res_curl)){//Проверяем, что запрос выполнен, и ответ нам вернулся
        if(isset($res_curl['result']['0']['ID'])) { //если контакт с телефоном в базе найден, то:
        $query = http_build_query(array(//формируем тело запроса
            'fields' => array(
                "TITLE" => 'new lead '.$email, //указываем названия лида
                "NAME" => $res_curl['result']['0']['NAME'],//Устанавливаем имя лиду
                "LAST_NAME" => $res_curl['result']['0']['LAST_NAME'],//Устанавливаем фамилию лиду
                "STATUS_ID" => "NEW",//статус новый
                "OPENED" => "Y",//Флаг "Доступна для всех"
                "ASSIGNED_BY_ID" => $ASSIGNED_BY_ID,// указываем ответственного
                "CONTACT_ID" => $res_curl['result']['0']['ID'],// привязка к лиду контакта (устанавливается статус "повторный лид")
            ),
            'params' => array("REGISTER_SONET_EVENT" => "Y")//произвести регистрацию события добавления лида в живой ленте. Дополнительно будет отправлено уведомление ответственному за лид.
        ));
        $res_curl = get_curl($url.'/rest/'.$admin_id.'/'.$token.'/'.$json_function.'.json',$query);//отправляем тело запроса на выполнения
        }
        else{//если контакта в поиске по номеру не было найдено, то:
            $query = http_build_query(array(
                'fields' => array(
                    "TITLE" => 'new lead '.$email,
                    "STATUS_ID" => "NEW",
                    "OPENED" => "Y",
                    "ASSIGNED_BY_ID" => $ASSIGNED_BY_ID,
                    "NAME" => "testName",
                    "LAST_NAME" => "lastTest",
                    "PHONE" => array(array("VALUE" => $PHONE, "VALUE_TYPE" => "WORK" )),
                    "EMAIL" => array(array("VALUE" => $email, "VALUE_TYPE" => "WORK" )),
                ),
                'params' => array("REGISTER_SONET_EVENT" => "Y")
            ));
            $res_curl = get_curl($url.'/rest/'.$admin_id.'/'.$token.'/'.$json_function.'.json',$query);
        $url.'/rest/'.$admin_id.'/'.$token.'/'.$json_function.'.json';
        }
    }
}



?>