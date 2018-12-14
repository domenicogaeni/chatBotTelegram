<?php
function getWatsonResponse($p_input_text,$numChat)
 {
   // Prelevo i dati dalla pagina
   //$contenuto = file_get_contents("INSERT YOUR URL");
   $dh = curl_init("INSERT YOUR URL"); 
   curl_setopt($dh, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($dh, CURLOPT_HEADER, 0);
   $contenuto = curl_exec($dh);
   curl_close($dh);
   //return $contenuto;

   // Prelevo tutti i dati che mi servono
   $dati = json_decode($contenuto,true);
   $temperatura = array();
   $umidita = array();
   $pressione = array();
   $temperatura[0]=$dati['temperatura'][0]['corrente'];
   $temperatura[1]=$dati['temperatura'][0]['max'];
   $temperatura[2]=$dati['temperatura'][0]['min'];

   $umidita[0]=$dati['umidita'][0]['corrente'];
   $umidita[1]=$dati['umidita'][0]['max'];
   $umidita[2]=$dati['umidita'][0]['min'];

   $pressione[0]=$dati['pressione'][0]['corrente'];
   $pressione[1]=$dati['pressione'][0]['max'];
   $pressione[2]=$dati['pressione'][0]['min'];

   $previsione=$dati['previsione'];

   //
   // COLLEGAMENTO CON IBM WATSON
   //
   $watson_username = "WATSON USERNAME";
   $watson_password = "WATSON PASSWORD";
   $watson_workspace_id = "WATSON WORKSPACE";

   $api_url = "https://gateway.watsonplatform.net/assistant/api/v1/workspaces/" . $watson_workspace_id . "/message?version=2018-07-10";

   if(!empty($p_input_text))
      $api_request_array['input']['text'] = $p_input_text;
   else
      $api_request_array['input'] = null;


   $json_api_request = json_encode($api_request_array);

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $api_url);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
   curl_setopt($ch, CURLOPT_USERPWD, "$watson_username:$watson_password");
   curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $json_api_request);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

   $response = curl_exec($ch);

   if($errno = curl_errno($ch))
   {
     $return_msg = 'Errore di connessione con l\'assistente Watson:(' . $errno . ') - ' . curl_strerror($errno);
   }

  // RISPOSTA
  $return_msg=$response;
  $obj = json_decode($return_msg,true);


  // Intento
  $intento=$obj['intents'][0]['intent'];
  // Testo restituito da Watson
  $text=$obj['output']['generic'][0]['text'];
  $stringa_out="";
  if($temperatura[0] == "N.D.")
  {
    $stringa_out.="La stazione meteo è momentaneamente offline.";
  }
  else
  {
    switch ($intento) {
      // TEMPERATURA
      case 'chiediTemperatura':
      if($text=="Decidi cosa: minimo massimo o attuale?")
      {
        $stringa_out.="La temperatura attuale è di ".$temperatura[0]." °C";
      }
      else
      {
        foreach ($obj['entities'] as $c => $v)
        {
          $enti = $v['entity'];
          switch ($enti)
          {
            case 'attuale':
            $stringa_out.="La temperatura attuale è di ".$temperatura[0]." °C\n";
            break;
            case 'massimo':
            $stringa_out.="La temperatura massima è di ".$temperatura[1]." °C\n";
            break;
            case 'minimo':
            $stringa_out.="La temperatura minima è di ".$temperatura[2]." °C\n";
            break;
          }
        }
      }
      //echo "OUTPUT: $stringa_out<br><br>";
      break;
      // UMIDITA
      case 'chiediUmidita':
      if($text=="Decidi cosa: minimo massimo o attuale?")
      {
        $stringa_out.="Il livello di umidità attuale è del ".$umidita[0]."%";
      }
      else
      {
        foreach ($obj['entities'] as $c => $v)
        {
          $enti = $v['entity'];
          switch ($enti)
          {
            case 'attuale':
            $stringa_out.="Il livello di umidità attuale è del ".$umidita[0]."%\n";
            break;
            case 'massimo':
            $stringa_out.="Il livello di umidità massimo è del ".$umidita[1]."%\n";
            break;
            case 'minimo':
            $stringa_out.="Il livello di umidità minimo è del ".$umidita[2]."%\n";
            break;
          }
        }
      }
      //echo "OUTPUT: $stringa_out<br><br>";
      break;
      // PRESSIONE
      case 'chiediPressione':
        if($text=="Decidi cosa: minimo massimo o attuale?")
        {
          $stringa_out.="Il livello di pressione attuale è di ".$pressione[0]."hPa";
        }
        else
        {
          foreach ($obj['entities'] as $c => $v)
          {
            $enti = $v['entity'];
            switch ($enti)
            {
              case 'attuale':
              $stringa_out.="Il livello di pressione attuale è di ".$pressione[0]."hPa\n";
              break;
              case 'massimo':
              $stringa_out.="Il livello di pressione massimo è di ".$pressione[1]."hPa\n";
              break;
              case 'minimo':
              $stringa_out.="Il livello di pressione minimo è di ".$pressione[2]."hPa\n";
              break;
            }
          }
        }
        //echo "OUTPUT: $stringa_out<br><br>";
        break;
      // PREVISIONE DEL TEMPO
      case 'chiediTempo':
        $stringa_out="Oggi il tempo risulta $previsione";
      break;
      // CHIEDERE INFORMAZIONI IN MERITO AL FUNZIONAMENTO DEL BOT
      case 'chiediInfo':
        $stringa_out="Sono il bot che affianca la Smart Weather Station\nPuoi chiedermi diverse informazioni come per esempio la temperatura attuale, oppure la pressione massima o l'umidità minima. Puoi chiedermi che tempo fa e ti risponderò direttamente con le ultime informazioni che ho ricevuto dalla Smart Weather Station.\nChiedimi per esempio: 'Che tempo fa?' oppure 'Dimmi la temperatura attuale'\nPuoi consultare i dati anche online al sito: http://smartweatherstation.altervista.org/";
      break;
      // INFORMAZIONI IN GENERALE
      case 'informazioniTotali':
      //echo "GENERALE: ";
      $stringa_out= "Ecco i dati di oggi:\nTEMPERATURA\nAttuale: ".$temperatura[0]."°C\nMassima: ".$temperatura[1]."°C\nMinima: ".$temperatura[2]."°C\n\nUMIDITA\nAttuale: ".$umidita[0]."%\nMassima: ".$umidita[1]."%\nMinima: ".$umidita[2]."%\n\nPRESSIONE\nAttuale: ".$pressione[0]."hPa\nMassima: ".$pressione[1]."hPa\nMinima: ".$pressione[2]."hPa\n\nIl tempo risulta $previsione";
      break;
      // ALTRI CASI
      case 'General_Greetings':
      $stringa_out = "Salve. Un caloroso benvenuto da parte del chatbot di aiuto della Smart Weather Station di Gaeni e Tognoli";
      break;

      default:
      $stringa_out= $text;
      break;

    }

  }

  return $stringa_out;

 }



  $content = file_get_contents("php://input");
  $update = json_decode($content, true);

  if(!$update)
  {
    exit;
  }

  $message = isset($update['message']) ? $update['message'] : "";
  $messageId = isset($message['message_id']) ? $message['message_id'] : "";
  $chatId = isset($message['chat']['id']) ? $message['chat']['id'] : "";
  $firstname = isset($message['chat']['first_name']) ? $message['chat']['first_name'] : "";
  $lastname = isset($message['chat']['last_name']) ? $message['chat']['last_name'] : "";
  $username = isset($message['chat']['username']) ? $message['chat']['username'] : "";
  $date = isset($message['date']) ? $message['date'] : "";
  $text = isset($message['text']) ? $message['text'] : "";

  $text = trim($text);
  $text = strtolower($text);
  if($text=="/start")
  {
    $text = "Salve. Un caloroso benvenuto da parte del chatbot di aiuto della Smart Weather Station di Gaeni e Tognoli.\nInserisci il codice della stazione meteo che vuoi monitorare, per esempio /0000.";
  }
  else if (strlen($text)==5&&$text[0]=='/')
  {
      // Inserimento codice
      $codiceStazione = substr(strtoupper($text),1);
      // Aggiungo l'utente nel db
      if($username=="")
        $username=$lastname. " " .$firstname;
      $ch = curl_init("http://smartweatherstation.altervista.org/aggiungiUtenti?username=$username&chatId=$chatId&codiceStazione=$codiceStazione"); // such as http://example.com/example.xml
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $data = curl_exec($ch);
      curl_close($ch);
      $text=$data;
  }
  else
  {
    $text = getWatsonResponse($text,$chatId);

    //$text="http://smartweatherstation.altervista.org/ottienijson.php?chatId=$chatId";
  }



  header("Content-Type: application/json");
  $parameters = array('chat_id' => $chatId, "text" => $text);
  $parameters["method"] = "sendMessage";
  echo json_encode($parameters);
