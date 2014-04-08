<?php
 require_once("class/MySqlDatabaseClass.php");
 require_once("class/UserClass.php");
 require_once("class/LoginClass.php");
 require_once("class/SessionClass.php");
 
 
 class OrderClass
 {
 	//Fields
 	private $order_id;
	private $order_short;
	private $order_long;
	private $deliverydate;
	private $eventdate;
	private $color;
	private $number_of_pictures;
 	private $cost;
	
 	//Constructor
 	public function __construct()
	{
		
	}
 	
 	public static function find_by_sql($query)
	{			
		global $database;
		$result = $database->fire_query($query);
		
		$order_object_array = array();
		
		while ( $row = mysql_fetch_array($result))
		{
			$array_object = new OrderClass();
			
			$array_object->order_id				= $row['order_id'];
			$array_object->order_short			= $row['order_short'];
			$array_object->order_long			= $row['order_long'];
			$array_object->deliverydate 		= $row['deliverydate'];
			$array_object->eventdate 			= $row['eventdate'];
			$array_object->color				= $row['color'];
			$array_object->number_of_pictures	= $row['number_of_pictures'];
			$array_object->cost					= $row['cost'];
			
			$order_object_array[] = $array_object;			
		}
		return $order_object_array;	
	}
	
	public static function insert_into_order($post_array)
	{
		global $database;
		
		$query = "INSERT INTO `order` (`order_id`,
									   `user_id`,
									   `order_short`,
									   `order_long`,
									   `deliverydate`,
									   `eventdate`,
									   `color`,
									   `number_of_pictures`,
									   `confirmed`)
				  VALUES			  (Null,
				  					   '".$_SESSION['id']."',
				  					   '".$post_array['order_short']."',
				  					   '".$post_array['order_long']."',
				  					   '".$post_array['deliverydate']."',
				  					   '".$post_array['eventdate']."',
				  					   '".$post_array['color']."',
				  					   '".$post_array['number_of_pictures']."',
				  					   'no')";
		
		$database->fire_query($query);
		$order_id = mysql_insert_id();
		self::send_order_activation_email($order_id, $post_array);
			
	}
	
	private static function send_order_activation_email($order_id, $post_array)
	{
		// Vind de voornaam, tussenvoegsel en achternaam	
		$user = UserClass::find_firstname_infix_surname();
		
		//Vind de gebruikersnaam en password
		$login_info = LoginClass::find_email_password_by_id();
		
		//Emailadres van de opdrachtgever
		$to = $login_info->get_email();
		
		//Aanhef van de email
		$subject = "Bevestigings-email opdracht FotoSjaak";
		
		$message = "Geachte heer/mevrouw: ".$user->getFirstname()." ".
											$user->getInfix()." ".
											$user->getSurname()."<br><br>";
		$message .= "Wij hebben de onderstaande order van u ontvangen<br>";
		$message .= "<table border='1'>
						<tr>
							<td>order_id</td>
							<td>".$order_id."</td>
						</tr>
						<tr>
							<td>Opdracht in het kort</td>
							<td>".$post_array['order_short']."</td>
						</tr>
						<tr>
							<td>Opdracht uitgebreid</td>
							<td>".$post_array['order_long']."</td>
						</tr>
						<tr>
							<td>Opleveringsdatum</td>
							<td>".$post_array['deliverydate']."</td>
						</tr>
						<tr>
							<td>Datum evenment</td>
							<td>".$post_array['eventdate']."</td>
						</tr>
						<tr>
							<td>Foto's worden genomen in </td>
							<td>".$post_array['color']."</td>
						</tr>
						<tr>
							<td>Aantal foto's</td>
							<td>".$post_array['number_of_pictures']."</td>
						</tr>
						<tr>
							<td>Opdracht bevestigd</td>
							<td>nee</td>
						</tr>
					 </table>";
		$message .= "Wanneer u op de onderstaande link klikt, gaat u<br>
					 akkoord met de algemene voorwaarden en is de order<br>
					 definitief.<br><br>";
		$message .= "<a href='http://localhost/2013-2014/Blok2/AM1A/fotosjaak-am1a/index.php?content=confirm_order&order_id=".$order_id."&email=".$login_info->get_email()."&password=".MD5($login_info->get_password())."'>opdracht is akkoord</a><br><br>";
		$message .= "Met vriendelijke groet,<br>
					 <b>Sjaak de Vries</b><br>
					 uw fotograaf";
												
		//echo $message;exit();
		$headers = "From: info@fotosjaak.nl\r\n";
		$headers .= "Reply-To: info@fotosjaak.nl\r\n";
		$headers .= "Cc: info@accountancy.nl\r\n";
		$headers .= "Bcc:info@belastingdiens.nl\r\n";
		$headers .= "X-mailer: PHP/".phpversion()."\r\n";
		$headers .= "MIME-version 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
		
			
		mail($to, $subject, $message, $headers);
	}
 	
	public static function update_confirmed($order_id)
	{
		global $database;	
		$query = "UPDATE `order`
				  SET `confirmed` = 'yes'
				  WHERE order_id = '".$order_id."'";
		$database->fire_query($query);
	}
	
	public static function find_orders_users()
	{
		global $database;
		// Maak een select query
		$query = "SELECT * FROM `order`, `user`
				  WHERE `order`.`user_id` = `user`.`id`
				  ORDER BY `order`.`user_id`";
		
		// Vuur de query af op de database
		$result = $database->fire_query($query);
		
		// Loop het result door met een while instructie
		while ($rows = mysql_fetch_array($result))
		{
			echo "<tr>
					<td>".$rows['id']."</td>
					<td>".$rows['firstname']."</td>
					<td>".$rows['infix']."</td>
					<td>".$rows['surname']."</td>
					<td>".$rows['order_id']."</td>
					<td>".$rows['order_short']."</td>
					<td>".$rows['deliverydate']."</td>
					<td><a href='index.php?
						content=upload_form&
						user_id=".$rows['id']."&
						order_id=".$rows['order_id']."'>up</a></td>			
				  </tr>";			
		}
		
	}
 
	public static function find_orders_by_user_id()
	{
		// Gebruik het $database-object uit de MySqlDatabaseClass
		global $database;
		
		// Maak een query die alle opdrachten selecteert uit de
		// Order tabel van de database voor degene die is ingelogd.
		$query = "SELECT * 
				  FROM `order`
				  WHERE `user_id` = ".$_SESSION['id'];
				
		// Vuur de query af op de database
		$result = $database->fire_query($query);
		
		// Lees de resultaten uit door middel van een while lus
		while ( $row = mysql_fetch_array($result))
		{
			echo "<tr>
					<td>".$row['user_id']."</td>
					<td>".$row['order_id']."</td>
					<td>".$row['order_short']."</td>
					<td>".date("d-m-Y",
							   strtotime($row['deliverydate']))."</td>
					<td>".$row['number_of_pictures']."</td>
					<td>".$row['color']."</td>
					<td>
						<a href='index.php?content=bekijk_fotos&order_id=".$row['order_id']."'>
 							<img src='images/show_fotos.png'
 			 					 alt='Bekijk de fotos' />
 	  					</a>					
					</td>
				  <tr>";
		}
	}

	public static function find_all_orders_order_by_id()
	{
		// Gebruik het $database-object uit de MySqlDatabaseClass
		global $database;
		
		// Maak een query die alle opdrachten selecteert uit de
		// Order tabel van de database voor degene die is ingelogd.
		$query = "SELECT * 
				  FROM `order`
				  ORDER BY `user_id`";
				
		// Vuur de query af op de database
		$result = $database->fire_query($query);
		
		// Lees de resultaten uit door middel van een while lus
		while ( $row = mysql_fetch_array($result))
		{
			echo "<tr>
					<td>".$row['user_id']."</td>
					<td>".$row['order_id']."</td>
					<td>".$row['order_short']."</td>
					<td>".date("d-m-Y",
							   strtotime($row['deliverydate']))."</td>
					<td>".$row['number_of_pictures']."</td>
					<td>".$row['color']."</td>
					<td><a href='index.php?content=add_cost&order_id=".$row['order_id']."'>".$row['cost']."</a></td>
					<td>
						<a href='index.php?content=bekijk_fotos_photographer&order_id=".$row['order_id']."&user_id=".$row['user_id']."'>
 							<img src='images/show_fotos.png'
 			 					 alt='Bekijk de fotos' />
 	  					</a>					
					</td>
				  <tr>";
		}
		
	}

	public static function update_cost_by_order_id($cost, $order_id)
	{
		global $database;
			
		$query = "UPDATE `order`
				  SET  `cost` =  '".$cost."'
				  WHERE  `order_id` = '".$order_id."'";	
		
		$database->fire_query($query);
		self::send_cost_to_customer($order_id);
		echo "Het ingevoerde bedrag is opgeslagen";	
		header("refresh:4;url=index.php?content=bekijk_opdracht_photographer");	
		
	}
	
	public static function send_cost_to_customer($order_id)
	{
		
			
		global $database;
		
		$query = "SELECT * 
				  FROM	`login`, `user`, `order`
				  WHERE	`order`.`order_id` = '".$order_id."'
				  AND	`login`.`id` = `user`.`id` 
				  AND	`user`.`id` = `order`.`user_id`";
				  
		$row = $database->fire_query($query);
		
		$row = mysql_fetch_array($row);
		//var_dump($result);
		
		$to = $row['email'];
		$subject = "Offerte opdracht:".$row['order_short'];
		$headers = "From: info@fotosjaak.nl\r\n";
		$headers .= "Reply-To: info@fotosjaak.nl\r\n";
		$headers .= "Cc: info@belastingdienst.nl\r\n";
		$headers .= "Bcc: info@interpol.nl\r\n";
		$headers .= "X-mailer: PHP/".phpversion()."\r\n";
		$headers .= "Mime-version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1\r\n";
		
		$message = "Geachte heer/mevrouw ".
					$row['firstname']." ".
					$row['infix']." ".
					$row['surname']."<br><br>
					
					
					Wij hebben de onderstaande opdracht van u ontvangen.
					<table style='border: 1px solid blue;'>
					  <tr>
					    <td>Uw order-id</td>
					    <td>".$row['order_id']."</td>
					  </tr>	
					  <tr>
					    <td>Korte omschrijving opdracht</td>
					    <td>".$row['order_short']."</td>
					  </tr>
					  <tr>
					    <td>Datum van aanvraag</td>
					    <td>".date("d-m-Y", strtotime($row['deliverydate']))."</td>
					  </tr>
					  <tr>
					    <td>Bedrag</td>
					    <td>".$row['cost']."</td>
					  </tr>				
					</table>
		Ik heb voor de bovenstaande opdracht een offerteprijs berekend van ".$row['cost']."<br>Wanneer u akkoord gaat met dit bedrag, klik dan <a href='http://localhost/2013-2014/Blok2/AM1A/fotosjaak-am1a/index.php?content=confirm_cost&order_id=".$row['order_id']."&user_id=".$row['id']."'>hier</a><br>
		<br>
		Met vriendelijke groet,<br>
		Sjaak de Vries,<br>
		Uw fotograaf.";
				
		
		mail($to, $subject, $message, $headers);
		
	}
	
	public static function update_confirm_cost($order_id, $user_id)
	{
		global $database;
		global $session;
		
		$query = "SELECT *
				  FROM `login`
				  WHERE `id` = '".$user_id."'";
		$userObjectArray = LoginClass::find_by_sql($query);
		$userObject = array_shift($userObjectArray);
		$session->login($userObject);
				  
		
		
		$query = "UPDATE `order`
				  SET `confirm_cost` = 'yes'
				  WHERE order_id = '".$order_id."'";
				  
		$database->fire_query($query);
		echo "Bedankt voor het bevestigen van de offerteprijs.<br>
		      We gaan voor u onmiddelijk aan de slag";
		header("refresh:4;index.php?content=login_form");
	}
}
?>