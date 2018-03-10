<?php

// Load configuration 
require("conf/conf.php"); 

// GLOBALI  
$token = TOKEN; 
$website = WEBSITE.$token; 
$my_chatID = MY_CHATID;     

// Credenziali database   
$servername = SERVERNAME;
$username = USERNAME;  
$password = PASSWORD;  
$dbname = DBNAME; 

// Aggiornamenti dal Bot
$updates = file_get_contents("php://input"); 
$updates_array = json_decode($updates, TRUE); 

// Informazioni aggiornamenti 
$text = $updates_array["message"]["text"]; 
$chatID = $updates_array["message"]["chat"]["id"]; 
$user_name = $updates_array["message"]["chat"]["username"]; 
$first_name = $updates_array["message"]["chat"]["first_name"]; 
$last_name =  $updates_array["message"]["chat"]["last_name"]; 
$group_title = $updates_array["message"]["chat"]["title"]; 
$new_chat_members = $updates_array["message"]["new_chat_members"]; 
$left_chat_member = $updates_array["message"]["left_chat_member"]; 
$video_gif = $updates_array["message"]["document"]; 
$audio_antonio = $updates_array["message"]["audio"]; 
$voice_antonio = $updates_array["message"]["voice"]; 
$photos = $updates_array["message"]["photo"]; 

 
// Connessione al Database 
$conn = new mysqli($servername, $username, $password, $dbname);  

// sendMessage($chatID,"Foto id: ".$photos[0]["file_id"]); 

// Controllo se il bot è attivo per l'utente o gruppo, se è iscritto, o se è un nuovo utente/gruppo   
$active=-1;  // Se =1 allora è attivo, =0 non attivo, =9 DEFAULT utente o gruppo non presente nel database 

// Controllo se l'utente/gruppo che ha appena scritto, è nel database, nel caso lo fosse, prendo il suo stato di attivo e lo stato di iscrizione 
$sql = "SELECT active FROM users WHERE chat_id = '".$chatID."'";   
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	// output data of each row
	while($row = $result->fetch_assoc()) {
	    $active = $row["active"]; 
	}
}   

// Utente o gruppo non presente nel database 
if($active==-1) { 
	// Sono stato aggiunto ad un gruppo nuovo  
	if($group_title != "") { 

		if($text == "") {     
			sendMessage($chatID,"Cesko Amore...NO!!! Il mio nome è Cesko Omare! Amore...amore se quello par le fighe, mi pincio dalla mattina alla sera de chele fighe internazionali! \n\nUsa /start per attivare il bot in questo gruppo\nUsa /info per ottenere informazioni sul bot e vedere tutti i comandi"); 
		}     

		if($text == "/start" || $text == "/start@IncantatoreDePhigheBot") {   
			// Inserisco il nuovo gruppo nel database 
			$sql = "INSERT INTO users (chat_id, group_name) VALUES ('".$chatID."', '".$group_title."')";   

			if ($conn->query($sql) === FALSE) {
				sendMessage($chatID,"Errore: ".$sql."\n".$conn->error); 
			} 

			// Imposto attivo a 1 = TRUE 
			$active = 1; 
		} 

	} else {   
		// Nuovo utente, deve avviare il bot  

		if($text == "/start" || $text == "/start@IncantatoreDePhigheBot") {   
		    // Inserisco il nuovo utente nel database  
		   
		    $sql = "INSERT INTO users (chat_id, first_name"; 
		    $sql_values = " VALUES ('".$chatID."', '".$first_name."'"; 

		    if($last_name != "") {
		    	$sql = $sql.", last_name"; 
		    	$sql_values = $sql_values.", '".$last_name."'"; 
		    }  
		    if($user_name != "") {
		    	$sql = $sql.", username"; 
		    	$sql_values = $sql_values.", '".$user_name."'"; 
		    }  

			$sql = $sql.")"; 
		    $sql_values = $sql_values.")";  

		    $sql = $sql.$sql_values;    

			if ($conn->query($sql) === FALSE) {   
				sendMessage($chatID,"Errore: ".$sql."\n".$conn->error);    
			}   

			// Imposto attivo e iscritto a 1 
			$active = 1;     	
	    }
	}       
}  
  
// Se il bot è attivo per l'utente o per il gruppo
if($active == 1) { 

	// Controllo nel database se l'utente ha scelto un'opzione del tipo Opinioni e Suggerimenti o Invia Messaggio 
	$sql = "SELECT `option` FROM users WHERE chat_id = '".$chatID."'"; 
	$option= -1;  // DEFAULT Indefinito 
	$result = $conn->query($sql);  
	if ($result->num_rows > 0) {
	   	// output data of each row
	   	while($row = $result->fetch_assoc()) {
	      	$option = $row["option"]; 
	   	}
	}   

	switch (true) { 
		// Prima controllo se l'utente ha selezionato un'opzione tipo Opinioni e suggerimenti o nel caso fosse amministratore, Invia messaggio 
		// In caso option fosse indefinito 
		case ($option == -1): 
	    	sendMessage($chatID,"Errore: Il bot risulta attivo per l'utente, ma quest'utlimo non è presente nel database");       
	    break; 
		// Se l'utente ha premuto Opinioni e Suggerimenti   
		case ($option == 1): 
			// Invia il messaggio all'amministratore 
			$message = ""; 
			if($user_name != "") {      
			  	$message = $message."Username: ".$user_name."\n"; 
			} 
			if($first_name != "") {      
			   	$message = $message."Name: ".$first_name;     

			   	if($last_name != "") {        
			   		$message = $message." ".$last_name;    
			   	}     

			   	$message = $message."\n"; 
			} 
				   			  
			if($group_title != "") {      
			   	$message = $message."Nome gruppo: ".$group_title."\n"; 
			}   

			$message = $message."Messaggio: \n".$text;   
			sendMessage($my_chatID,$message);    
			sendMessage($chatID,"Grazie per il tuo feedback");  

			// Rimetti il count a 0 dopo che l'utente ha inviato il feedback 
			$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
				  
			if ($conn->query($sql) === FALSE) {  
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
			} 	
		break; 
		// L'amministratore ha selezionato Invia messaggio   
		case ($option == 2): 
			$sql = "SELECT chat_id FROM users"; 

			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
		   		// output data of each row
		   		while($row = $result->fetch_assoc()) {
		      		sendMessage($row["chat_id"],$text); 
		   		}
			} else {
		    	sendMessage($chatID,"Nessun utente iscritto");  
			} 
		        
	        // Metti a 0 option dopo aver eseguito il comando 
			$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
				  
			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
			}   
		break; 
		// Inserisci GIF  
		case ($option == 3): 

			// Metti a 0 option dopo aver eseguito il comando 
			if($text == "Fatto") {   
				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione"); 
				}   
			} else {   
				if($video_gif["file_id"] != "") {
					$sql = "INSERT INTO gif (file_id) VALUES ('".$video_gif["file_id"]."')"; 

					if ($conn->query($sql) === FALSE) {   
						sendMessage($chatID,"Errore: ".$sql."\n".$conn->error);    
					} else {
						sendMessage($chatID,"GIF Aggiunta");  
					} 
				} else {
					sendMessage($chatID,"File non valido per la sezione GIF");  	
				}    
			}     
		break; 
		// Inserisci Audio Antonio
		case ($option == 4): 

			// Metti a 0 option dopo aver eseguito il comando  
			if($text == "Fatto") {
				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione"); 
				} 
			} else {
				if($audio_antonio["file_id"] != "" || $voice_antonio["file_id"] != "" ) {    
					if($audio_antonio["file_id"] != "") {
						$audio_voice_antonio = $audio_antonio["file_id"]; 
						$type = "audio";  
					} else {
						$audio_voice_antonio = $voice_antonio["file_id"]; 
						$type = "voice";  
					} 

					$sql = "INSERT INTO audio_Antonio (file_id, type) VALUES ('".$audio_voice_antonio."', '".$type."')"; 

					if ($conn->query($sql) === FALSE) {   
						sendMessage($chatID,"Errore: ".$sql."\n".$conn->error);    
					} else {
						sendMessage($chatID,"Audio aggiunto");  
					} 
					
				} else {  
					sendMessage($chatID,"File non valido per la sezione Antonio"); 
				}
			}
		break; 
		// Inserisci foto 
		case ($option == 5):  		
			// Metti a 0 option dopo aver eseguito il comando 
			if($text == "Fatto") {  
				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione");   
				}
			} else {   
				if($photos[0]["file_id"] != "") {
					$sql = "INSERT INTO photos (file_id) VALUES ('".$photos[0]["file_id"]."')"; 

					if ($conn->query($sql) === FALSE) {   
						sendMessage($chatID,"Errore: ".$sql."\n".$conn->error);    
					} else {
						sendMessage($chatID,"Foto aggiunta");  
					} 
				} else {
					sendMessage($chatID,"File non valido per la sezione Foto");  
				}    
			} 
		break;  
		// Elimina GIF   
		case ($option == 6):  	    

			$file_id_min = ""; 
			// Metti a 0 option dopo aver eseguito il comando 
			if($text == "Fatto") { 
				$not_done = 0;  
					  
				$sql="UPDATE users SET previous_id = NULL WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				}    

				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione");   
				}   

			} else { 
			
				$sql = "SELECT previous_id FROM users WHERE chat_id = '".$chatID."'"; 
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
			   		// output data of each row
			   		while($row = $result->fetch_assoc()) {
			      		$previous_id = $row["previous_id"]; 
			   		}
				} else {
			    	sendMessage($chatID,"Utente non presente");  
				}  

				if($previous_id == "") {   
					$sql = "SELECT MIN(file_id) FROM gif"; 
					$result = $conn->query($sql);   
					if ($result->num_rows > 0) {
				   		// output data of each row
				   		while($row = $result->fetch_assoc()) {
				      		$file_id_min = $row["MIN(file_id)"]; 
				   		}
					} else {
				    	sendMessage($chatID,"GIF non trovata");  
					} 

					$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
					if ($conn->query($sql) === FALSE) {
			   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
					} 

					sendDocument($chatID,$file_id_min); 


				} else { 
					if($text == "Successiva") {  
						$sql = "SELECT file_id FROM gif WHERE file_id = (SELECT MIN(file_id) FROM gif WHERE file_id > '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}
						} else {
					    	sendMessage($chatID,"GIF finite");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						sendDocument($chatID,$file_id_min); 
					} 
					if($text == "Precedente") {
						$sql = "SELECT file_id FROM gif WHERE file_id = (SELECT MAX(file_id) FROM gif WHERE file_id < '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}  
						} else {
					    	sendMessage($chatID,"GIF finite");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						sendDocument($chatID,$file_id_min); 
					}
				}   
			}  

			if($text == "Elimina GIF") {  
				$sql = "DELETE FROM gif WHERE file_id = (SELECT previous_id FROM users WHERE chat_id = '".$chatID."')"; 

				if ($conn->query($sql) === FALSE) {
					sendMessage($chatID,"Nessuna GIF selezionata");  
		  	    } else {
		  	    	sendMessage($chatID,"GIF eliminata"); 
		  	    }
			}  
			
		break;   
		// Elimina Audio Antonio  
		case ($option == 7):  	
			
			$file_id_min = ""; 
			// Metti a 0 option dopo aver eseguito il comando 
			if($text == "Fatto") { 
					  
				$sql="UPDATE users SET previous_id = NULL WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				}    

				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione");   
				}   

			} else {  
			
				$sql = "SELECT previous_id FROM users WHERE chat_id = '".$chatID."'"; 
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
			   		// output data of each row
			   		while($row = $result->fetch_assoc()) {
			      		$previous_id = $row["previous_id"]; 
			   		}
				} else {
			    	sendMessage($chatID,"Utente non presente");  
				}  

				if($previous_id == "") {   
					$sql = "SELECT MIN(file_id) FROM audio_Antonio"; 
					$result = $conn->query($sql);   
					if ($result->num_rows > 0) {
				   		// output data of each row
				   		while($row = $result->fetch_assoc()) {
				      		$file_id_min = $row["MIN(file_id)"]; 
				   		}
					} else {
				    	sendMessage($chatID,"Audio non trovato");  
					} 

					$sql = "SELECT type FROM audio_Antonio WHERE file_id = '".$file_id_min."'"; 
					$result = $conn->query($sql);   
					if ($result->num_rows > 0) {
				   		// output data of each row
				   		while($row = $result->fetch_assoc()) {
				      		$type = $row["type"]; 
				   		}
					} else {
				    	sendMessage($chatID,"Audio non trovato");  
					} 

					$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
					if ($conn->query($sql) === FALSE) {
			   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
					} 

					if($type == "audio") {   
						sendAudio($chatID,$file_id_min); 
					} else {   
						sendVoice($chatID,$file_id_min); 
					}    

				} else { 
					if($text == "Successiva") {  
						$sql = "SELECT file_id FROM audio_Antonio WHERE file_id = (SELECT MIN(file_id) FROM audio_Antonio WHERE file_id > '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}
						} else {
					    	sendMessage($chatID,"Audio finiti");  
						} 

						$sql = "SELECT type FROM audio_Antonio WHERE file_id = '".$file_id_min."'"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$type = $row["type"]; 
					   		}
						} else {
					    	sendMessage($chatID,"Audio non trovato");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						if($type == "audio") {   
							sendAudio($chatID,$file_id_min); 
						} else {   
							sendVoice($chatID,$file_id_min); 
						}   
					} 
					if($text == "Precedente") {
						$sql = "SELECT file_id FROM audio_Antonio WHERE file_id = (SELECT MAX(file_id) FROM audio_Antonio WHERE file_id < '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}  
						} else {
					    	sendMessage($chatID,"Audio finiti");  
						} 

						$sql = "SELECT type FROM audio_Antonio WHERE file_id = '".$file_id_min."'"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$type = $row["type"]; 
					   		}
						} else {
					    	sendMessage($chatID,"Audio non trovato");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						if($type == "audio") {   
							sendAudio($chatID,$file_id_min); 
						} else {   
							sendVoice($chatID,$file_id_min); 
						}   
					}
				}   
			}  

			if($text == "Elimina audio") {  
				$sql = "DELETE FROM audio_Antonio WHERE file_id = (SELECT previous_id FROM users WHERE chat_id = '".$chatID."')"; 

				if ($conn->query($sql) === FALSE) {
					sendMessage($chatID,"Nessun audio selezionato");  
		  	    } else {
		  	    	sendMessage($chatID,"Audio eliminato"); 
		  	    }
			}  
			
		break; 
		// Elimina foto 
		case ($option == 8):  	

			$file_id_min = ""; 
			// Metti a 0 option dopo aver eseguito il comando 
			if($text == "Fatto") { 
					  
				$sql="UPDATE users SET previous_id = NULL WHERE chat_id = '".$chatID."'";   
					  
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				}    

				$sql="UPDATE users SET `option` = 0 WHERE chat_id = '".$chatID."'";   
				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
				} else {
					sendKeyboardAdmin($chatID,"Pannello amministrazione");   
				}   

			} else  {  
		
				$sql = "SELECT previous_id FROM users WHERE chat_id = '".$chatID."'"; 
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
			   		// output data of each row
			   		while($row = $result->fetch_assoc()) {
			      		$previous_id = $row["previous_id"]; 
			   		}
				} else {
			    	sendMessage($chatID,"Utente non presente");  
				}  

				if($previous_id == "") {   
					$sql = "SELECT MIN(file_id) FROM photos"; 
					$result = $conn->query($sql);   
					if ($result->num_rows > 0) {
				   		// output data of each row
				   		while($row = $result->fetch_assoc()) {
				      		$file_id_min = $row["MIN(file_id)"]; 
				   		}
					} else {
				    	sendMessage($chatID,"Foto non trovata");  
					} 

					$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
					if ($conn->query($sql) === FALSE) {
			   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
					} 

					sendPhoto($chatID,$file_id_min); 


				} else { 
					if($text == "Successiva") {  
						$sql = "SELECT file_id FROM photos WHERE file_id = (SELECT MIN(file_id) FROM photos WHERE file_id > '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}
						} else {
					    	sendMessage($chatID,"Foto finite");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						sendPhoto($chatID,$file_id_min); 
					} 
					if($text == "Precedente") {
						$sql = "SELECT file_id FROM photos WHERE file_id = (SELECT MAX(file_id) FROM photos WHERE file_id < '".$previous_id."')"; 
						$result = $conn->query($sql);   
						if ($result->num_rows > 0) {
					   		// output data of each row
					   		while($row = $result->fetch_assoc()) {
					      		$file_id_min = $row["file_id"]; 
					   		}  
						} else {
					    	sendMessage($chatID,"Foto finite");  
						} 

						//sendMessage($chatID,"New previous: ".$file_id_min); 
						$sql = "UPDATE users SET previous_id = '".$file_id_min."' WHERE chat_id = '".$chatID."'"; 
						if ($conn->query($sql) === FALSE) {
				   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
						} 

						sendPhoto($chatID,$file_id_min); 
					}
				}   
			}  

			if($text == "Elimina foto") {  
				$sql = "DELETE FROM photos WHERE file_id = (SELECT previous_id FROM users WHERE chat_id = '".$chatID."')"; 

				if ($conn->query($sql) === FALSE) {
					sendMessage($chatID,"Nessuna foto selezionata");  
		  	    } else {
		  	    	sendMessage($chatID,"Foto eliminata"); 
		  	    }
			}  
			
		break;   
		// Comandi presenti nella Custom Keyboard     
	    case ($text == "Ciao Marzia"):   
	    	$urlaudio = "https://emanuelecrema.altervista.org/audio/CiaoMarzia.mp3"; 
	        sendAudio($chatID, $urlaudio);   
	        break; 
	    case ($text == "El Pero"): 
	    	$urlaudio = "https://emanuelecrema.altervista.org/audio/ElPero.ogg"; 
	        sendAudio($chatID, $urlaudio);   
	        break;  
	    case ($text == "Incantatore"): 
	    	$urlaudio = "https://emanuelecrema.altervista.org/audio/Incantatore.ogg"; 
	        sendAudio($chatID, $urlaudio);   
	        break;  
	    case ($text == "Varda laaa"): 
	    	$urlaudio = "https://emanuelecrema.altervista.org/audio/Vardalaaa.ogg"; 
	        sendAudio($chatID, $urlaudio);   
	        break; 
	    case ($text == "Antonio"): 
				$sql = "SELECT file_id, type FROM audio_Antonio ORDER BY RAND() LIMIT 1";   
				$result = $conn->query($sql); 
				if ($result->num_rows > 0) {
    				// output data of each row
   					while($row = $result->fetch_assoc()) { 
   						if($row["type"] == "audio") { 
        					sendAudio($chatID,$row["file_id"]);  
        				}  
        				else {
        					sendVoice($chatID,$row["file_id"]);  
        				} 
    				}   
				} else {
    				sendMessage($chatID,"Nessun audio di Antonio presente");   
				}   
	    break;    
	    case ($text == "Opinioni e suggerimenti"):  
			
			$sql="UPDATE users SET `option` = 1 WHERE chat_id = '".$chatID."'";   
			 
			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
			}
			
		    sendMessage($chatID,"Lascia qui un'opinione o un suggerimento..."); 
	    break; 
	    // Comandi start e stop per utente attivo 
		// Se un utente attivo usa /start, invia la tastiera 
	    case ($text == "/start@IncantatoreDePhigheBot" || $text == "/start"): 
	    	sendKeyboard($chatID,"Benventuo in questo Bot! HIIIHHH");  
	    break; 
	    // Se un utente attivo usa /stop, elimina l'iscrizione e disattiva il bot   
	    case ($text == "/stop@IncantatoreDePhigheBot" || $text == "/stop"):      
		    sendMessage($chatID,"Hai arrestato il Bot e l'iscrizione. Usa /start per farlo ripartire");   
			$sql = "DELETE FROM users WHERE chat_id=".$chatID;  

			if ($conn->query($sql) === FALSE) {
				sendMessage($chatID,"Errore nell'eliminazione del record: ".$conn->error);  
			}      

	    break; 
	    // Se qualcuno preme Foto e gif, invio un'altra tastiera  
	    case ($text == "Foto e gif"): 
			sendKeyboard2($chatID,"Qui ci sono foto e gif"); 
	    break; 
	    case (strstr($text,"Torna indietro")): 
	    	sendKeyboard($chatID,"Menu principale");   
	    break; 
	    // Comando per salutare qualcuno   
	    case (strtolower(substr($text,0,6)) === "saluta"): 
	    	$message = "Ciaooo ".substr($text, 7).$text[strlen($text)-1].$text[strlen($text)-1]."! HIIIHHH";   
	    	sendMessage($chatID,$message); 
		break; 
		case (strtolower($text) == "impreca"):   
			$val = rand(1,4);  
			$message = ""; 
			switch ($val) {  
			 	case 1:
			 		sendMessage($chatID,"Zio po' Brigante, mi me ciamo Cesko...OMARE!");  
			 		break;  
			 	case 2:  
			 		sendMessage($chatID,"Zio Canguro, a go insegnà mi a balare");  
			 		break;  
			 	case 3:   
			 		sendMessage($chatID,"Zio e po' Cestari");   
			 		break;  
			 	case 4:   
			 		sendMessage($chatID,"Zio l**maro, ogni pugno ne copo uno, punto secondo se me fasì incazzre divento cativo come na iena");   
			 		break; 	
			 	default:
			 		sendMessage($chatID,"A ghe sta un errore, come ghenti fato a finir qua"); 
			 		break;
			} 
		break; 
	    // Invia la tastiera e invia un messaggio con il significato dei comandi 
	    case ($text == "/tastiera" || $text == "/tastiera@IncantatoreDePhigheBot"): 
	    	$message = "Comandi della Custom keyboard \n"; 
	    	$message = $message."1. Antonio - Invia un audio tra i tanti di Antonio, un imitatore\n"; 
	    	$message = $message."2. Ciao Marzia - Invia l'audio di Antonio in cui dice Ciao Marzia\n"; 
	    	$message = $message."3. El Pero - presenta un suo amico in un audio\n"; 
	    	$message = $message."4. Incantatore - Audio riguardante gli incantatori di serpenti e lui di incantatore di fighe\n";  
	    	$message = $message."5. Varda laaa - Rivolto al Pero che non guarda bene in camera\n"; 
	    	$message = $message."6. Foto e gif - Collezione di foto e gif\n";   
	    	$message = $message."  a. Foto - Collezione di foto\n"; 
	    	$message = $message."  b. GIF - Collezione di gif\n";    
	    	$message = $message."7. Opinioni e suggerimenti - Premere questo pulsante e poi scrivere qualcosa per inviare un messaggio all'amministratore\n\n"; 
	    	sendKeyboard($chatID,$message); 
	    break; 
	    // Per sapere le parole a cui reagisce il bot  
	    case ($text == "/parolechiave" || $text == "/parolechiave@IncantatoreDePhigheBot"): 
	    	$message = "Il bot reagirà con frasi se i vostri messaggi contengono queste sottostringhe quindi anche se sono concatenate ad altri caratteri\n"; 
	    	$message = $message."1. 'pesce' o 'pesse'\n"; 
	    	$message = $message."2. 'seee'\n"; 
	    	$message = $message."3. 'hi'\n"; 
	    	$message = $message."4. 'ciao'\n"; 
	    	$message = $message."5. 'byte', 'gb' o 'mb'\n"; 
	    	$message = $message."6. 'uni'\n"; 
	    	$message = $message."7. 'prof'\n"; 
	    	$message = $message."8. 'laurea' o 'lauree'\n";  
	    	$message = $message."9. 'music'\n"; 
	    	$message = $message."10. 'figa', 'vagina', 'patat' o 'fighe'\n"; 
	    	$message = $message."11. 'donn', 'ragazza', 'ragazze' o 'gnocche'\n"; 
	    	$message = $message."12. 'amor'\n\n"; 
	    	sendMessage($chatID,$message); 
		break; 
 		// Per avere informazioni su altri comandi testuali 
	    case ($text == "/altricomandi" || $text == "/altricomandi@IncantatoreDePhigheBot"): 
	    	$message = "Altri comandi di testo \n"; 
	    	$message = $message."1. Saluta {nome} \n"; 
	    	$message = $message."2. Impreca  \n"; 
	   
	    	sendMessage($chatID,$message); 
		break;   
		case ($text == "GIF"): 
		
			$sql = "SELECT file_id FROM gif ORDER BY RAND() LIMIT 1"; 
			$result = $conn->query($sql); 
			if ($result->num_rows > 0) {
				// output data of each row
					while($row = $result->fetch_assoc()) { 
					sendDocument($chatID,$row["file_id"]);  
				}   
			} else {
				sendMessage($chatID,"Nessuna gif presente");   
			}   
			
		break;  
		case ($text == "Foto"): 
	
			$sql = "SELECT file_id FROM photos ORDER BY RAND() LIMIT 1"; 
			$result = $conn->query($sql); 
			if ($result->num_rows > 0) {
				// output data of each row
					while($row = $result->fetch_assoc()) { 
    				sendPhoto($chatID,$row["file_id"]);  
				}   
			} else {
				sendMessage($chatID,"Nessuna foto presente");   
			}   	

		break;  
		case (strtolower($text) == "admin" && $chatID == $my_chatID): 
			sendKeyboardAdmin($chatID,"Pannello amministrazione"); 
		break; 
		case ($text == "Aggiungi GIF" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 3 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendMessage($chatID,"Metti qui la gif da aggiungere..."); 
	    	sendKeyboardFatto($chatID,"Una volta inserite le gif, premere Fatto per confermare"); 

		break; 
		case ($text == "Aggiungi audio Antonio" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 4 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendMessage($chatID,"Metti qui l'audio da aggiungere..."); 
	    	sendKeyboardFatto($chatID,"Una volta inserite gli audio, premere Fatto per confermare");  

		break; 
		case ($text == "Aggiungi foto" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 5 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendMessage($chatID,"Metti qui la foto da aggiungere..."); 
	    	sendKeyboardFatto($chatID,"Una volta inserite le foto, premere Fatto per confermare"); 

		break; 
		case ($text == "Rimuovi risposte" && $chatID == $my_chatID): 

	    	sendMessage($chatID,"Scegli cosa rimuovere"); 
	    	sendKeyboardRisposte($chatID,"Selezionare le risposte e premere Elimina"); 

		break;   
		case ($text == "Rimuovi GIF" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 6 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendKeyboardElimina($chatID,"Premi elimina GIF per eliminare una GIF, premere Fatto una volta finito"); 

		break;  
		case ($text == "Rimuovi audio Antonio" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 7 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendKeyboardEliminaAudio($chatID,"Premi elimina audio per eliminare un audio, premere Fatto una volta finito"); 

		break;  
		case ($text == "Rimuovi foto" && $chatID == $my_chatID): 
			$sql="UPDATE users SET `option` = 8 WHERE chat_id = '".$chatID."'";    

			if ($conn->query($sql) === FALSE) {
		   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
		   	}  

	    	sendKeyboardEliminaFoto($chatID,"Premi elimina foto per eliminare una foto, premere Fatto una volta finito"); 

		break;  
	    // Comando per ricevere informazioni e istruzioni riguardanti il Bot   
	    case ($text == "/info" || $text == "/info@IncantatoreDePhigheBot"): 
	    	$message = "Cesko Omare\n\n";    

	    	$message = $message."Il bot dedicato all'ononimo personaggio\nIdeato per essere aggiunto ai gruppi\n\n";    

	    	$message = $message."Lista comandi principali \n"; 
	    	$message = $message."/start - per avviare il bot e ricevere gli aggiornamenti\n"; 
	    	$message = $message."/stop - per arrestare il bot e non ricevere più gli aggiornamenti\n"; 
	    	$message = $message."/tastiera - per visalizzare la tastiera e avere informazioni sui comandi\n";  
	    	$message = $message."/parolechiave - per vedere le parole a cui reagisce il bot\n"; 
	    	$message = $message."/altricomandi - per vedere informazioni su altri comandi di testo\n";   
	    	$message = $message."/info - per avere informazioni e istruzioni riguardanti il bot\n\n";    

	    	$message = $message."Digitare 'Tasi Cesko' per fare in modo che non risponda più ai comandi o parole chiave, e digitare 'Vien qua Cesko per riattivarlo'\n\n"; 

	    	$message = $message."Potete trovare questo bot anche qui \nstorebot.me/bot/incantatoredephighebot \n\n"; 

	    	$message = $message."Usate pure Opinioni e suggerimenti per suggerire all'amministratore dei miglioramenti o segnalare bugs\n\n"; 

	    	$message = $message."Arrivederci "; 

	    	sendMessage($chatID,$message);  

		break;   
		default: 

		    // Frasi inviate quando l'utente usa una parola chiave
			if(strstr(strtolower($text),"pesce") || strstr(strtolower($text),"pesse")) {
				$message = 'Mi go el pesse, che quando che te lo ciapi in man el cresse'; 
				sendMessage($chatID,$message); 
			}   
			if(strstr(strtolower($text),"seee")) {
				$message = 'Bevetelo ti el prosecco'; 
				sendMessage($chatID,$message); 
			} 
			if(strstr(strtolower($text),"hi")) {
				$message = 'HIIIHHH'; 
				sendMessage($chatID,$message); 
			} 
			if(strstr(strtolower($text),"ciao")) {  
				$message = 'Ciaoo Marziaaa'; 
				sendMessage($chatID,$message); 
			}  
			if(strstr(strtolower($text),"byte") || strstr(strtolower($text),"gb") || strstr(strtolower($text),"mb"))  {  
				$message = 'Seto quanto ca lè un megabyte? Milioni de bytes'; 
				sendMessage($chatID,$message); 
			}
			if(strstr(strtolower($text),"uni")) {  
				$message = 'Mi posso insegnar in te le meio università d Italia'; 
				sendMessage($chatID,$message); 
			}  
			if(strstr(strtolower($text),"prof")) {  
					$message = 'I professori cossa che non i te insegna un cazzo! Ghe insegno mi ai professori'; 
				sendMessage($chatID,$message); 
			}  
			if(strstr(strtolower($text),"laurea") || strstr(strtolower($text),"lauree")) {  
				$message = 'Mi ghe no zinque'; 
				sendMessage($chatID,$message); 
			} 
			if(strstr(strtolower($text),"music")) {  
				$message = 'A te si un autoctono della musica! La musica, bisogna sentirla dentro'; 
				sendMessage($chatID,$message); 
			}   
			if(strstr(strtolower($text),"figa") || strstr(strtolower($text),"vagina") || strstr(strtolower($text),"patat") || strstr(strtolower($text), "fighe")) {  
				$val = rand(0,1); 
				if($val==0) {
					if(!strstr(strtolower($text), "fighe")) {
						$message = 'Ale done bisogna volerghe ben, non lasciarle lì come pecore'; 
					} else {
						$message = 'Le meio fighe delle tre Venezie me le pincio miii'; 
					} 
				} else {
					$message = 'Mi quando go sborà dentro le pol nare a fanculo!'; 
				} 
				sendMessage($chatID,$message); 
			}   
			if(strstr(strtolower($text),"donn") || strstr(strtolower($text),"ragazza") || strstr(strtolower($text),"ragazze") || strstr(strtolower($text),"gnocca")) {  
				$val = rand(0,1); 
				if($val==0) {  
					$message = 'Ale done bisogna volerghe ben, non lasciarle lì come pecore';  
				} else {
					$message = 'Mi ghe voi ben a tutte!'; 
				}   
				sendMessage($chatID,$message); 
			} 
			if(strstr(strtolower($text),"amor")) {  
				$message = 'Amore, amore, mi me ciamo Cesko OMARE, no "amore"!'; 
				sendMessage($chatID,$message); 
			}  

			// Comando per fermare il Bot in maniera che non risponda ai comandi, si riceveranno comunque i messaggi da parte dell'amministratore 
			if(strtolower($text) == "tasi cesko") {   
				$message = 'Va ben, vo a bere, ciameme col comando Vien qua Cesko'; 
				sendMessage($chatID,$message); 	
				$sql = "UPDATE users SET active = 0 WHERE chat_id = '".$chatID."'"; 
				if ($conn->query($sql) === FALSE) {  
		   			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error); 
		   		}   
			}  

			// Comando per inviare un messaggio in broadcast a tutti gli iscritti, valido solo per l'amministratore  
			if(strtolower($text) == "invia messaggio" && $chatID == $my_chatID) {
				$option=9; 
		    	$sql = "SELECT `option` FROM users WHERE chat_id = '".$chatID."'";  
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
		   		 	// output data of each row
		   			 while($row = $result->fetch_assoc()) {
		      			 $option = $row["option"]; 
		   			 }
				} else {
		    		sendMessage($chatID,"Nessun utente trovato");    
				} 
 
				$sql="UPDATE users SET `option` = 2 WHERE chat_id = '".$chatID."'";    

				if ($conn->query($sql) === FALSE) {
			   		sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);    
			   	}  

		    	sendMessage($chatID,"Scrivi il messaggio da inviare in broadcast..."); 
			}  

			// Comando per visualizzare gli iscritti e il numero, solo per l'amministratore 
			if(strtolower($text) == "visualizza iscritti" && $chatID == $my_chatID) { 
				$message = ""; 
			
				$sql = "SELECT * FROM users";  
				$result = $conn->query($sql);
				if ($result->num_rows > 0) { 
					$ct=0; 
		   		 	// output data of each row
		   			 while($row = $result->fetch_assoc()) { 
		   			 	if($row["username"] != "") {      
		   			 		$message = $message."Username: ".$row["username"]."\n"; 
		   			 	} 
		   			 	if($row["first_name"] != "") {      
		   			 		$message = $message."Name: ".$row["first_name"];     

		   			 		if($row["last_name"] != "") {        
		   			 			$message = $message." ".$row["last_name"];    
		   			 		} 

		   			 		$message = $message."\n"; 
		   			 	} 
			   			  
		   			 	if($row["group_name"] != "") {      
		   			 		$message = $message."Nome gruppo: ".$row["group_name"]."\n"; 
		   			 	}   

		   			 	$message = $message."\n";  

		   			 	$ct++; 
		   			 }     
				} else {
		    		sendMessage($chatID,"Nessun utente trovato");  
				} 

				$message = "\n".$message."Numero di iscritti: ".$ct; 
		    	sendMessage($chatID,$message); 
			}

			break; 
	}   

	// Se qualcuno è entrato nel gruppo, manda un messaggio di benventuo 
	if($new_chat_members[0]["first_name"] != "") { 
		sendMessage($chatID,"Gente! Date il benventuo a ".$new_chat_members[0]["first_name"]."!!!"); 
	} 

	// Se qualcuno lascia il gruppo, invita gli altri a riaggiungerlo 
	if($left_chat_member["first_name"]!="") { 
		sendMessage($chatID,"Do cazzo veto ".$left_chat_member["first_name"]."!!! Felo rientrare!"); 
	} 

} else {

	// Se il bot è stato disattivato, non risponderà ai comandi, ma l'utente riceverà comunque i messaggi 

	// "vien qua cesko" per riattivare il Bot
	if(strtolower($text) == "vien qua cesko") {   
		$message = 'Eccome'; 
		sendMessage($chatID,$message); 	
		$sql = "UPDATE users SET active = 1 WHERE chat_id = '".$chatID."'"; 
		if ($conn->query($sql) === FALSE) {   
			sendMessage($chatID,"Errore nell'aggiornamento del record: ".$conn->error);  
		}      
	}  
}       

function sendMessage($chatID, $message) { 
    $url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.urlencode($message); 
    file_get_contents($url);  
}   

function sendKeyboard($chatID,$message) { 
	$cmarzia = urlencode("Ciao Marzia"); 
	$elpero = urlencode("El Pero"); 
	$vardala = urlencode("Varda laaa"); 
	$opinioni = urlencode("Opinioni e suggerimenti"); 
	$foto_e_gif = urlencode("Foto e gif");    

	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Antonio","'.$cmarzia.'","'.$elpero.'"],["Incantatore","'.$vardala.'","'.$foto_e_gif.'"],["'.$opinioni.'"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}   

function sendKeyboard2($chatID,$message) { 
	$torna_indietro = urlencode("\u25C0 Torna indietro");  

	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Foto"],["GIF"],["'.$torna_indietro.'"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendKeyboardRisposte($chatID,$message) { 
	$rimuovi_gif = urlencode("Rimuovi GIF"); 
	$rimuovi_foto = urlencode("Rimuovi foto"); 
	$rimuovi_audio_antonio = urlencode("Rimuovi audio Antonio"); 
	$torna_indietro = urlencode("\u25C0 Torna indietro");  

	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["'.$rimuovi_gif.'","'.$rimuovi_foto.'"],["'.$rimuovi_audio_antonio.'"],["'.$torna_indietro.'"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendKeyboardAdmin($chatID,$message) { 
	$aggiungi_audio_ant = urlencode("Aggiungi audio Antonio"); 
	$aggiungi_gif = urlencode("Aggiungi GIF"); 
	$aggiungi_foto = urlencode("Aggiungi foto"); 
	$invia_messaggio = urlencode("Invia messaggio"); 
	$visualizza_iscritti = urlencode("Visualizza iscritti"); 
	$torna_indietro = urlencode("\u25C0 Torna indietro"); 
	$rimuovi_risposte = urlencode("Rimuovi risposte");    

	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["'.$aggiungi_audio_ant.'"],["'.$aggiungi_gif.'","'.$aggiungi_foto.'"],["'.$invia_messaggio.'","'.$visualizza_iscritti.'"],["'.$rimuovi_risposte.'"],["'.$torna_indietro.'"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendKeyboardFatto($chatID,$message) { 

	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Fatto"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
} 

function sendKeyboardElimina($chatID,$message) { 
	$elimina_gif = urlencode("Elimina GIF"); 
	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Precedente","Successiva"],["'.$elimina_gif.'"],["Fatto"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendKeyboardEliminaAudio($chatID,$message) { 
	$elimina_gif = urlencode("Elimina audio"); 
	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Precedente","Successiva"],["'.$elimina_gif.'"],["Fatto"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendKeyboardEliminaFoto($chatID,$message) { 
	$elimina_foto = urlencode("Elimina foto"); 
	$message = urlencode($message); 

    $keyboard = '&reply_markup={"keyboard":[["Precedente","Successiva"],["'.$elimina_foto.'"],["Fatto"]],"resize_keyboard":true}'; 

	$url = $GLOBALS[website].'/sendmessage?chat_id='.$chatID.'&text='.$message.$keyboard; 
    file_get_contents($url); 
}  

function sendAudio($chatID, $urlaudio) {
	$url = $GLOBALS[website].'/sendaudio?chat_id='.$chatID.'&audio='.$urlaudio; 
    file_get_contents($url); 
}  

function sendVideo($chatID, $urlvideo) {
	$url = $GLOBALS[website].'/sendvideo?chat_id='.$chatID.'&video='.$urlvideo; 
    file_get_contents($url); 
} 

function sendDocument($chatID, $urldocument) {   
	$url = $GLOBALS[website].'/senddocument?chat_id='.$chatID.'&document='.$urldocument; 
    file_get_contents($url); 
} 

function sendPhoto($chatID, $urlphoto) {   
	$url = $GLOBALS[website].'/sendphoto?chat_id='.$chatID.'&photo='.$urlphoto; 
    file_get_contents($url); 
}  

function sendVoice($chatID, $urlvoice) {   
	$url = $GLOBALS[website].'/sendvoice?chat_id='.$chatID.'&voice='.$urlvoice; 
    file_get_contents($url); 
}  

?>    