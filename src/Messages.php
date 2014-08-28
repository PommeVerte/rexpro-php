<?php

namespace brightzone\rexpro;

/**
 * RexPro PHP client Messages class
 * Builds and parses binary messages for communication with RexPro
 * 
 * @category DB
 * @package  Rexpro
 * @author   Dylan Millikin <dylan.millikin@brightzone.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 apache2
 * @link     https://github.com/tinkerpop/rexster/wiki/RexPro-Messages
 */
class Messages
{
	/**
	 * Serializer types
	 */
	const SERIALIZER_MSGPACK = 0;
	const SERIALIZER_JSON = 1;
	
	
	/**
	 * Message types
	 */
	const ERROR = 0;
	const SESSION_REQUEST = 1;
	const SESSION_RESPONSE = 2;
	const SCRIPT_REQUEST = 3;
	const CONSOLE_SCRIPT_RESPONSE = 4;
	const SCRIPT_RESPONSE_MESSAGE = 5;
	const GRAPHSON_SCRIPT_RESPONSE = 6;
	
	/**
	 * Error types
	 */
	const CHANNEL_CONSOLE = 1;
	const CHANNEL_MSGPACK = 2;
	const CHANNEL_GRAPHSON = 3;
	
	/**
	 * @var string 16 byte request identifier
	 * Has no particular use at the moment but could be used in non-blocking mode
	 */
	public $requestUuid;
	
	/**
	 * @var string Most recently built binary message
	 */
	public $msgPack;
	
	/**
	 * @var string Most recently built binary message
	 */
	private $_serializer;

	/**
	 * Overriding construct to populate _serializer
	 *
	 * @param int $serializer serializer object to use for packing and unpacking of messages
	 * 
	 * @return void
	 */
	public function __construct($serializer)
	{
		$this->_serializer = $serializer;
	}
	
	/**
	 * Create and set request UUID
	 * 
	 * @return string the UUID
	 */
	public function createUuid()
	{
		return $this->requestUuid = Helper::createUuid();
	}
	
	/**
	 * Constructs full binary message (including outter envelope) For use in Session creation
	 * 
	 * @param string $sessionUuid     session ID. This is not necessary at this stage but still included
	 * @param string $username        Username to use for connection to rexpro server
	 * @param string $password        Password to use for connection to rexpro server
	 * @param array  $meta            Metadata to add to request message
	 * @param int    $protocolVersion Protocol to use, only current option is 0
	 * 
	 * @return string Returns binary data to be written to socket
	 */
	public function buildSessionMessage($sessionUuid, $username, $password, $meta, $protocolVersion=0)
	{
		$this->createUuid();
		
		//build message array
		$message = array(
				$sessionUuid,
				$this->requestUuid,
				array_merge(array('killSession'=>FALSE), $meta),//let caller overwrite (session close for instance)
				$username,
				$password
		);
		
		//lets pack the message
		$messageLength = $this->_serializer->serialize($message);
		
		//Now we need to build headers
		$msg = pack('C*',
					$protocolVersion,
					$this->_serializer->getValue(),
					0, //reserved byte
					0, //reserved byte
					0, //reserved byte
					0, //reserved byte
					self::SESSION_REQUEST).Helper::convertIntTo32Bit($messageLength);
		
		//append message and return
		$this->msgPack = $msg.$message;
		//echo $this->msgPack;
		return $this->msgPack;
	}	
	
	/**
	 * Constructs full binary message (including outter envelope) For use in script execution
	 * 
	 * @param string $sessionUuid     session ID. This is not necessary at this stage but still included
	 * @param string $script          Gremlin (groovy flavored) script to run
	 * @param array  $bindings        Associated bindings
	 * @param array  $meta            Metadata to add to request message
	 * @param int    $protocolVersion Protocol to use, only current option is 0
	 * 
	 * @return string Returns binary data to be written to socket
	 */
	public function buildScriptMessage($sessionUuid, $script, $bindings, $meta, $protocolVersion=0)
	{
		//lets start by packing message
		$this->createUuid();
		
		//build message array
		$message = array(
				$sessionUuid,
				$this->requestUuid,
				array_merge(array('inSession'=>TRUE), $meta),//overwrite user value
				'groovy',
				$script,
				($bindings === NULL? new \stdClass : $bindings)		
				);
		
		//lets pack the message
		$messageLength = $this->_serializer->serialize($message);
		
		//Now we need to build headers
		$msg = pack('C*',
					$protocolVersion,
					$this->_serializer->getValue(),
					0, //reserved byte
					0, //reserved byte
					0, //reserved byte
					0, //reserved byte
					self::SCRIPT_REQUEST).Helper::convertIntTo32Bit($messageLength);
		
		//append message and return
		$this->msgPack = $msg.$message;
		return $this->msgPack;	
	}	
	
	/**
	 * Parses full message (including outter envelope)
	 * 
	 * @param string $bin binary Data from server packet
	 * 
	 * @return array Array containing all results
	 */
	public function parse($bin)
	{
		$resp = str_split($bin, 1);
		
		$proVersion = Helper::convertIntFrom32Bit($resp[0]); //cheating by using this function on non-32bit
		$serializerType = Helper::convertIntFrom32Bit($resp[1]); //cheating by using this function on non-32bit
		$rqstType = Helper::convertIntFrom32Bit($resp[6]); //cheating by using this function on non-32bit
		
		$mssgLength = implode('', array_slice($resp, 7, 4));
		$mssgLength = Helper::convertIntFrom32Bit($mssgLength);

		$mssg = $this->_serializer->unserialize(implode('', array_slice($resp, 11, count($resp))));
		
		return array($proVersion, $serializerType, $rqstType, $mssgLength,$mssg);
	}
}
