<?php
/**
 *    twilio-callbox
 *    
 *    Author: Ryan Boswell / ryanboswell.com
 *    
 *    
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *    
 *    
 *    
 *    Usage:
 *    
 *    1) Register a number with Twilio and point it at this script.
 *    2) Set config variables below.
 *    3) Enjoy!
 *    
 */


/**
 * Whatever you want your callbox to announce itself as.
 */
 $config['greeting'] = "This is the apartment callbox for some badass people.";

/**
 * Whatever gate code your callbox uses to buzz guests in.
 */
 $config['gate_code'] = "7";

/**
 * An array of everyone listed as a contact for your callbox.
 * List up to 9 or only 8 if you wish to use the secret code to enable quick access.
 */
 $config['roommates'] = array(	array("name" => 'Ryan', "number" => "415-555-5555"),
					 			array("name" => 'Mary', "number" => "415-555-5555") );
/**
 * The secret code you wish to use, you can disable this feature by setting
 * it to NULL.
 */
 $config['secret'] = "1234";

/**
 * The voice you want the callbox to use, either "man" or "woman"
 */
 $config['voice'] = "woman";



/**
 * Below here is where the magic happens.
 * Unless you know what you're doing, don't touch anything below.
 */
 require_once("twilio.php");
 $twiml = new TwimlResponse();
 
 $parts = explode('/', $_SERVER["PHP_SELF"]);
 $config['filename'] = $parts[count($parts) - 1];

 switch($_GET['page'])
 {
	 case "gather": // handle routing to a roommate or to the secret code prompt
		 $index = ($_REQUEST['Digits']-1);
		 
		if($config['secret'] && $_REQUEST['Digits'] == '9'):
			// secret code is enabled and they accessed the secret menu, prompt for the code
			$gather = $twiml->addGather(array( "action" => $config['filename']."?page=secret", "numDigits" => strlen($config['secret']), "method" => "POST" ));
			$gather->addSay("Please enter the secret code now.", array("voice" => $config['voice']));
			
		elseif(isset($config['roommates'][$index])):
			// entered the digit for a valid roommate, forward the call
			$roommate = $config['roommates'][$index];
			$twiml->addSay("Connecting you to ".$roommate['name'], array("voice" => $config['voice']));
			$twiml->addDial($roommate['number']);
		endif;
		
		// not a valid input, send back to the menu
		$twiml->addRedirect($config['filename']);
	 break;
	 
	 case "secret": // handle the secret code entry
	 	if($config['secret'] && ($_REQUEST['Digits'] == $config['secret'])):
	 		// secret code matches, buzz the caller in
	 		$twiml->addSay("Buzzing you in now.", array("voice" => $config['voice']));
	 		$twiml->addPlay("http://www.dialabc.com/i/cache/dtmfgen/wavpcm8.300/".$config['gate_code'].".wav");
	 	endif;
	 	
	 	// either the feature is disabled or they didn't enter the proper code, send back to the menu
	 	$twiml->addRedirect($config['filename']);
	 break;
	 
	 default: // provide the main menu
	 	$twiml->addPause( array("length" => "2") );
	 	$gather = $twiml->addGather(array( "action" => $config['filename']."?page=gather", "numDigits" => "1", "method" => "POST" ));
	 	$gather->addSay($config['greeting'], array("voice" => $config['voice']));
	 	
	 	// loop through each roommate for the menu
	 	foreach($config['roommates'] as $num => $roommate):
	 		$gather->addPause( array("length" => "1") );
	 		$gather->addSay("For ".$roommate['name'].", press ".($num+1).".", array("voice" => $config['voice']));
	 	endforeach;
	 break;
 }
 
 // output all of the XML generated
 $twiml->Respond();
