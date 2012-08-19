<?php

	/*
	Copyright (c) 2009-2010 Twilio, Inc.

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
	*/    
	
	// Twiml Response Helpers
	// ========================================================================
	
	/*
	 * TwimlVerb: Base class for all TwiML TwimlVerbs used in creating Responses
	 * Throws a TwilioException if an non-supported attribute or
	 * attribute value is added to the TwimlVerb. All methods in TwimlVerb are protected
	 * or private
	 */
	 
	class TwimlVerb {
		private $tag;
		private $body;
		private $attr;
		private $children;
			
		/*
		 * __construct 
		 *   $body : TwimlVerb contents 
		 *   $body : TwimlVerb attributes
		 */
		function __construct($body=NULL, $attr = array()) {
			if (is_array($body)) {
				$attr = $body;
				$body = NULL;
			}
			$this->tag = substr(get_class($this), 5);
			$this->body = $body;
			$this->attr = array();
			$this->children = array();
			self::addAttributes($attr);
		}
		
		/*
		 * addAttributes
		 *     $attr  : A key/value array of attributes to be added
		 *     $valid : A key/value array containging the accepted attributes
		 *     for this TwimlVerb
		 *     Throws an exception if an invlaid attribute is found
		 */
		private function addAttributes($attr) {
			foreach ($attr as $key => $value) {
				if(in_array($key, $this->valid))
					$this->attr[$key] = $value;
				else
					throw new TwilioException($key . ', ' . $value . 
					   " is not a supported attribute pair");
			}
		}

		/*
		 * append
		 *     Nests other TwimlVerbs inside self.
		 *     @param TwimlVerb $verb Item to append
		 *     @return TwimlVerb same as $verb parameter
		 */
		function append($verb) {
			if(is_null($this->nesting))
				throw new TwilioException($this->tag ." doesn't support nesting");
			else if(!is_object($verb))
				throw new TwilioException($verb->tag . " is not an object");
			else if(!in_array(get_class($verb), $this->nesting))
				throw new TwilioException($verb->tag . " is not an allowed verb here");
			else {
				$this->children[] = $verb;
				return $verb;
			}
		}
		
		/*
		 * set
		 *     $attr  : An attribute to be added
		 *    $valid : The attrbute value for this TwimlVerb
		 *     No error checking here
		 */
		function set($key, $value){
			$this->attr[$key] = $value;
		}
	
		/* Convenience Methods */
		/* @return TwimlSay Say verb
		*/
		function addSay($body=NULL, $attr = array()){
			return self::append(new TwimlSay($body, $attr));    
		}
		
		/* @return TwimlPlay Play verb
		*/
		function addPlay($body=NULL, $attr = array()){
			return self::append(new TwimlPlay($body, $attr));    
		}
		
		/* @return TwimlDial Dial verb
		*/
		function addDial($body=NULL, $attr = array()){
			return self::append(new TwimlDial($body, $attr));    
		}
		
		/* @return TwimlNumber Number verb
		*/
		function addNumber($body=NULL, $attr = array()){
			return self::append(new TwimlNumber($body, $attr));    
		}
		
		/* @return TwimlGather Gather verb
		*/
		function addGather($attr = array()){
			return self::append(new TwimlGather($attr));    
		}
		
		/* @return TwimlRecord Record verb
		*/
		function addRecord($attr = array()){
			return self::append(new TwimlRecord(NULL, $attr));    
		}
		
		/* @return TwimlHangup Hangup verb
		*/
		function addHangup(){
			return self::append(new TwimlHangup());    
		}
		
		/* @return TwimlRedirect Redirect verb
		*/
		function addRedirect($body=NULL, $attr = array()){
			return self::append(new TwimlRedirect($body, $attr));    
		}
		
		/* @return TwimlPause Pause verb
		*/
		function addPause($attr = array()){
			return self::append(new TwimlPause($attr));
		}
		
		/* @return TwimlConference Conference verb
		*/
		function addConference($body=NULL, $attr = array()){
			return self::append(new TwimlConference($body, $attr));    
		}
		
		/* @return TwimlSms Sms verb
		*/
		function addSms($body=NULL, $attr = array()){
			return self::append(new TwimlSms($body, $attr));    
		}
		
		/*
		 * write
		 * Output the XML for this TwimlVerb and all it's children
		 *    $parent: This TwimlVerb's parent TwimlVerb
		 *    $writeself : If FALSE, TwimlVerb will not output itself,
		 *    only its children
		 */
		protected function write($parent, $writeself=TRUE){
			if($writeself) {
				$elem = $parent->addChild($this->tag, htmlspecialchars($this->body));
				foreach($this->attr as $key => $value)
					$elem->addAttribute($key, $value);
				foreach($this->children as $child)
					$child->write($elem);
			} else {
				foreach($this->children as $child)
					$child->write($parent);
			}
			
		}
		
	}
	
	
	class TwimlResponse extends TwimlVerb {
		
		private $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Response></Response>";
		
		protected $nesting = array('TwimlSay', 'TwimlPlay', 'TwimlGather', 'TwimlRecord', 
			'TwimlDial', 'TwimlRedirect', 'TwimlPause', 'TwimlHangup', 'TwimlSms');
		
		function __construct(){
			parent::__construct(NULL);
		}
		
		function Respond($sendHeader = true) {
			// try to force the xml data type
			// this is generally unneeded by Twilio, but nice to have
			if($sendHeader)
			{
				if(!headers_sent())
				{
					header("Content-type: text/xml");
				}
			}
			$simplexml = new SimpleXMLElement($this->xml);
			$this->write($simplexml, FALSE);
			print $simplexml->asXML();
		}
		
		function asURL($encode = TRUE){
			$simplexml = new SimpleXMLElement($this->xml);
			$this->write($simplexml, FALSE);
			if($encode)
				return urlencode($simplexml->asXML());
			else
				return $simplexml->asXML();
		}
		
	}
	
	class TwimlSay extends TwimlVerb {
	
		protected $valid = array('voice','language','loop');
	
	}

	class TwimlReject extends TwimlVerb {
		
		protected $valid = array('reason');
			
	}
	
	class TwimlPlay extends TwimlVerb {
		
		protected $valid = array('loop');
	
	}
	
	
	class TwimlRecord extends TwimlVerb {
	
		protected $valid = array('action','method','timeout','finishOnKey',
								 'maxLength','transcribe','transcribeCallback', 'playBeep');
	
	}
	
	
	class TwimlDial extends TwimlVerb {
	
		protected $valid = array('action','method','timeout','hangupOnStar',
			'timeLimit','callerId');
	
		protected $nesting = array('TwimlNumber','TwimlConference');
	
	}
	
	class TwimlRedirect extends TwimlVerb {
	
		protected $valid = array('method');
	
	}
	
	class TwimlPause extends TwimlVerb {
	
		protected $valid = array('length');
	
		function __construct($attr = array()) {
			parent::__construct(NULL, $attr);
		}
	
	}
	
	class TwimlHangup extends TwimlVerb {
	
		function __construct() {
			parent::__construct(NULL, array());
		}
	
	
	}
	
	class TwimlGather extends TwimlVerb {
	
		protected $valid = array('action','method','timeout','finishOnKey',
			'numDigits');
			
		protected $nesting = array('TwimlSay', 'TwimlPlay', 'TwimlPause');
		
		function __construct($attr = array()){
			parent::__construct(NULL, $attr);
		}
	
	}
	
	class TwimlNumber extends TwimlVerb {
	
		protected $valid = array('url','sendDigits');
			
	}
	
	class TwimlConference extends TwimlVerb {
	
		protected $valid = array('muted','beep','startConferenceOnEnter',
			'endConferenceOnExit','waitUrl','waitMethod');
			
	}
	
	class TwimlSms extends TwimlVerb {
		protected $valid = array('to', 'from', 'action', 'method', 'statusCallback');
	}
	
