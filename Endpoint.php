<?php
/**
 * @git git@github.com:BorderCloud/SPARQL.git
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/
*/
require_once("Curl.php");
require_once("Net.php");
require_once("Base.php");
require_once("ParserSparqlResult.php");

/**
 * Sparql HTTP Client for SPARQL1.1's Endpoint
 * 
 * You can send a query to any endpoint sparql
 * and read the result in an array.
 * 
 * Example : send a simple query to DBpedia
 * <code>
 * <?php
 * 
 * require_once('bordercloud/Endpoint.php');
 * 
 *     $endpoint ="http://dbpedia.org/";
 *     $sp_readonly = new Endpoint($endpoint);
 *  $q = "select *  where {?x ?y ?z.} LIMIT 5";
 *  $rows = $sp_readonly->query($q, 'rows');
 *  $err = $sp_readonly->getErrors();
 *  if ($err) {
 *       print_r($err);
 *       throw new Exception(print_r($err,true));
 *     }
 * 
 *  foreach($rows["result"]["variables"] as $variable){
 *  	printf("%-20.20s",$variable);
 *  	echo '|';
 *  }
 *  echo "\n";
 *  
 *  foreach ($rows["result"]["rows"] as $row){
 *  	foreach($rows["result"]["variables"] as $variable){
 *  		printf("%-20.20s",$row[$variable]);
 *  	echo '|';
 *  	}
 *  	echo "\n";
 *  }
 * ?>
 * </code>
 *
 * For the different server, you can use the property setEndpointQuery,
 * setEndpointUpdate,setNameParameterQueryRead or setNameParameterQueryWrite.
 * 
 * EXAMPLE to config : Virtuoso
 * $sp_readonly = new Endpoint("http://localhost/tests/",$modeRead,$modeDebug);

 * EXAMPLE to config : 4Store
 * $sp_readonly = new Endpoint("http://localhost/",$modeRead,$modeDebug);

 * EXAMPLE to config : Sesame
 * $sp_readonly = new Endpoint("",$modeRead,$modeDebug);
 * $sp_readonly->setEndpointQuery("http://localhost/openrdf-sesame/repositories/tests");
 * $sp_readonly->setEndpointUpdate("http://localhost/openrdf-sesame/repositories/tests/statements");

 * EXAMPLE to config : Fuseki
 * $sp_readonly = new Endpoint("",$modeRead,$modeDebug);
 * $sp_readonly->setEndpointQuery("http://localhost/tests/query");
 * $sp_readonly->setEndpointUpdate("http://localhost/tests/update");

 * EXAMPLE to config : Allegrograph
 * $sp_readonly = new Endpoint("",$modeRead,$modeDebug);
 * $sp_readonly->setEndpointQuery("http://localhost/repositories/tests");
 * $sp_readonly->setEndpointUpdate("http://localhost/repositories/tests");
 * $sp_readonly->setNameParameterQueryWrite("query");
 * 
 *  With a query ASK, you can use the parameter 'raw'
 *  in the function query and read directly the result true or false.
 * 
 * Example : send a query ASK with the parameter raw
 * <code>
 * <?php
 *    $q = "PREFIX a: <http://example.com/test/a/>
 *            PREFIX b: <http://example.com/test/b/>
 *            ask where { GRAPH <".$graph."> {a:A b:Name \"Test3\" .}} ";
 *    $res = $sp_readonly->query($q, 'raw');
 *    $err = $sp_readonly->getErrors();
 *    if ($err) {
 *        print_r($err);
 *        throw new Exception(print_r($err,true));
 *    }
 *    var_dump($res);
 * ?>
 * </code>
 * 
 * You can insert data also with SPARQL and the function query in your graphs.
 * The BorderCloud's service can host your graphs ( http://www.bordercloud.com ).
 * You can choose your graph's name and Bordercloud will give you a code.
 * With 3 parameters, you are alone to update your graph.
 * 
 * Example : send a query Insert
 * <code>
 *     $sp_write = new Endpoint($MyEndPointSparql,$MyCode,$MyGraph);
 *     echo "\nInsert :";
 *     $q = "
 *             PREFIX a: <http://example.com/test/a/>
 *             PREFIX b: <http://example.com/test/b/>
 *             INSERT DATA {
 *                 GRAPH <".$MyGraph."> {
 *                 a:A b:Name \"Test1\" .
 *                 a:A b:Name \"Test2\" .
 *                 a:A b:Name \"Test3\" .
 *             }}";
 *     $res = $sp_write->query($q,'raw');
 *     $err = $sp_write->getErrors();
 *     if ($err) {
 *         print_r($err);
 *         throw new Exception(print_r($err,true));
 *     }
 *     var_dump($res);
 * </code>
 *  
 * Example : send a query Delete
 * <code>
 *     $sp_write = new Endpoint($MyEndPointSparql,$MyCode,$MyGraph);
 * 
 *     echo "\nDelete :";
 *     $q = "
 *             PREFIX a: <http://example.com/test/a/>
 *             PREFIX b: <http://example.com/test/b/>
 *             DELETE DATA {
 *                 GRAPH <".$MyGraph."> {
 *                 a:A b:Name \"Test2\" .
 *             }}";
 *     
 *     $res = $sp_write->query($q,'raw');
 *     $err = $sp_write->getErrors();
 *     if ($err) {
 *         print_r($err);
 *         throw new Exception(print_r($err,true));
 *     }
 *     var_dump($res);
 * </code>
 *  
 *  You can change the format of the response with the function
 *  QueryRead and QueryUpdate.
 */
class Endpoint extends Base {
	/**
	 * Root of the URL Endpoint
	 * @access private
	 * @var string
	 */	 
	private $_endpoint_root;
	
	/**
	 * URL of Endpoint to read
	 * @access private
	 * @var string
	 */
	private $_endpoint;
		
	/**
	 * URL  sparql to write
	 * @access private
	 * @var string
	 */
	private $_endpoint_write;
	
	/**
	 * in the constructor set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	private $_debug;
	
	/**
	 * in the constructor set the right to write or not in the store
	 * @access private
	 * @var string
	 */
	private $_readOnly;
	
	/**
	 * in the constructor set the proxy_host if necessary
	 * @access private
	 * @var string
	 */
	private $_proxy_host;
	
	/**
	 * in the constructor set the proxy_port if necessary
	 * @access private
	 * @var int
	 */
	private $_proxy_port;
	
	/**
	 * Parser of XML result
	 * @access private
	 * @var ParserSparqlResult
	 */
	private $_parserSparqlResult;
	
	/**
	 * Name of parameter HTTP GET to send a query SPARQL to read data.
	 * @access private
	 * @var string
	 */
	private $_nameParameterQueryRead;
	
	/**
	 * Name of parameter HTTP POST to send a query SPARQL to write data.
	 * @access private
	 * @var string
	 */
	private $_nameParameterQueryWrite;
	
	/** For Arc2 **/
// 	private $_arc2_RemoteStore;
// 	private $_arc2_Reader;
// 	private $_config;

	/**
	 * Constructor of Graph
	 * @param string $endpoint : url of endpoint, example : http://lod.bordercloud.com/sparql
	 * @param boolean $readOnly : true by default, if you allow the function query to write in the database
	 * @param boolean $debug : false by default, set debug to true in order to get usefull output
	 * @param string $proxy_host : null by default, IP of your proxy
	 * @param string $proxy_port : null by default, port of your proxy
	 * @access public
	 */
	public function __construct($endpoint,
								$readOnly = true,
								$debug = false,
								$proxy_host = null,
								$proxy_port = null)
	{				
		parent::__construct();
		
		if($readOnly){
			$this->_endpoint = $endpoint;
		}else{
			if (preg_match("|/sparql/?$|i", $endpoint)) {
				$this->_endpoint = $endpoint;
				$this->_endpoint_root = preg_replace("|^(.*/)sparql/?$|i", "$1", $endpoint);
			} else {
				$this->_endpoint_root = $endpoint;
				$this->_endpoint = 	$this->_endpoint_root."sparql/";
			}
		}
	
		$this->_debug = $debug;
		$this->_endpoint_write = $this->_endpoint_root."update/"; 
		$this->_readOnly = $readOnly;
		
		$this->_proxy_host = $proxy_host;
		$this->_proxy_port = $proxy_port;		
		
		if($this->_proxy_host != null && $this->_proxy_port != null){
			$this->_config = array(
				/* remote endpoint */
			  'remote_store_endpoint' => $this->_endpoint,
				  /* network */
			  'proxy_host' => $this->_proxy_host,
			  'proxy_port' => $this->_proxy_port,			
			);
		}else{
			$this->_config = array(
			/* remote endpoint */
			  'remote_store_endpoint' => $this->_endpoint,
			);			
		}
		
		// init parameter in the standard
		$this->_nameParameterQueryRead = "query";
		$this->_nameParameterQueryWrite = "update";		

		//init parser
 		$this->_parserSparqlResult = new ParserSparqlResult(); 		
 		
	}
	
	/**
	 * Set the url to read
	 * @param string $url : endpoint's url to read
	 * @access public
	 */
	public function setEndpointQuery($url) {
		$this->_endpoint = $url;
	}
	
	/**
	 * Get the url to read
	 * @return string $url : endpoint's url to read
	 * @access public
	 */
	public function getEndpointQuery() {
		return $this->_endpoint;
	}
	
	/**
	 * Set the url to write
	 * @param string $url : endpoint's url to write
	 * @access public
	 */
	public function setEndpointUpdate($url) {
		$this->_endpoint_write = $url;
	}
		
	/**
	 * Get the url to write
	 * @return string $url : endpoint's url to write
	 * @access public
	 */
	public function getEndpointUpdate() {
		return $this->_endpoint_write;
	}
	
	/**
	 * Set the parameter in the query to write
	 * @param string $name : name of parameter
	 * @access public
	 */
	public function setNameParameterQueryWrite($name) {
		$this->_nameParameterQueryWrite = $name;
	}
	
	/**
	 * Get the parameter in the query to write
	 * @return string $name : name of parameter
	 * @access public
	 */
	public function getNameParameterQueryWrite() {
		return $this->_nameparameterQueryWrite;
	}
	
	/**
	 * Set the parameter in the query to read
	 * @param string $name : name of parameter
	 * @access public
	 */
	public function setNameParameterQueryRead($name) {
		$this->_nameParameterQueryRead = $name;
	}
	
	/**
	 * Get the parameter in the query to read
	 * @return string $name : name of parameter
	 * @access public
	 */
	public function getNameParameterQueryRead() {
		return $this->_nameparameterQueryRead;
	}
	
	/**
	 * Check if the server is up.
	 * @return boolean true if the triplestore is up.
	 * @access public
	 */
	public function check() {
		return Net::ping($this->_endpoint) != -1;
	}
	
	/**
	 * This function parse a SPARQL query, send the query and parse the SPARQL result in a array. 
	 * You can custom the result with the parameter $result_format : 
	 * <ul>
	 * <li>rows to return array of results
	 * <li>row to return array of first result
	 * <li>raw to return boolean for request ask, insert and delete
	 * </ul>
	 * @param string $q : Query SPARQL 
	 * @param string $result_format : Optional,  rows, row or raw
	 * @return array|boolean in function of parameter $result_format
	 * @access public
	 */
	public function query($q, $result_format = 'rows') {	
		$t1 = Endpoint::mtime();
		$response = $this->queryRead($q);
		xml_parse($this->_parserSparqlResult->getParser(),$response, true);		
		$result = $this->_parserSparqlResult->getResult();	
		if(! array_key_exists("result",$result)){
			$message = "Error parsing SPARQL result:\n Message XML result (in theory) :>>>>>>>\n".$response."\n<<<<<<<<\n";
			$error = $this->errorLog($q,null, $this->_endpoint,200,$message);
			$this->addError($error);
			return false;
		}
		$result['query_time'] =   Endpoint::mtime() - $t1 ;
		switch($result_format)
		{
			case "row" :
				return $result["result"]["rows"][0];
			case "raw" :
				return $result["result"]["rows"][0][0];
			default: //rows				
				return $result;
		}
	}
		
	/**
	* Send a request SPARQL of type select or ask to endpoint directly and output the response
	* of server. If you want parse the result of this function, it's better and simpler
	* to use the function query().
	*
	* @param string $query : Query Sparql
	* @param string $typeOutput by default "application/sparql-results+xml",
	* @return string response of server or false if error (to do getErrors())
	* @access public
	*/
	public function queryRead($query,$typeOutput="application/sparql-results+xml" ) {
		$client = $this->initCurl();
		$sUri    = $this->_endpoint;	
		$response ="";
		
		if($typeOutput == null){
			$data = array($this->_nameParameterQueryRead =>   $query);
			$response = $client->send_post_data($sUri,$data);
		}else{
			$data = array($this->_nameParameterQueryRead =>   $query,
			"Accept" => $typeOutput); //fix for sesame
			$response = $client->send_post_data($sUri,$data,array('Accept: '.$typeOutput));
		}		

		$code = $client->get_http_response_code();
			
		$this->debugLog($query,$sUri,$code,$response);
	
		if($code < 200 || $code >= 300)
		{
			$error = $this->errorLog($query,$data,$sUri,$code,$response);
			$this->addError($error);
			return false;
		}
		return $response;
	}

	/**
	 * Send a request SPARQL of type insert data or delete data to endpoint directly.
	 * <ul>
	 * <li>Example insert : PREFIX ex: <http://example.com/> INSERT DATA { GRAPH <http://mygraph> { ex:a ex:p 12 .}}
	 * <li>Example delete : PREFIX ex: <http://example.com/> DELETE DATA { GRAPH <http://mygraph> { ex:a ex:p 12 .}}
	 * </ul>
	 * @param string $query : Query Sparql of type insert data or delete data only
	 * @param string $typeOutput by default "application/sparql-results+xml",
	 * @return boolean true if it did or false if error (to do getErrors())
	 * @access public
	 */
	public function queryUpdate($query,$typeOutput="application/sparql-results+xml") { 
			$client = $this->initCurl();
			$sUri  =   $this->_endpoint_write;			
			$response ="";
		
			if($typeOutput == null){
				$data = array($this->_nameParameterQueryWrite =>   $query);
				$response = $client->send_post_data($sUri,$data);
			}else{
				$data = array($this->_nameParameterQueryWrite =>   $query,
				"Accept" => $typeOutput); //fix for sesame
				$response = $client->send_post_data($sUri,$data,array('Accept: '.$typeOutput));
			}		
			
			$code = $client->get_http_response_code();

			$this->debugLog($query,$sUri,$code,$response);
					
			if($code < 200 || $code >= 300 ){
				$error = $this->errorLog($query,$data,$sUri,$code,$response);
				$this->addError($error);
				return false;
			}
			//echo "OK".$response;
			return $response;
        }
		
	/************************************************************************/
	//PRIVATE Function
	
	static function mtime(){
		list($msec, $sec) = explode(" ", microtime());
		return ((float)$msec + (float)$sec);
	}
	
	/**
	 * write error for human
	 * @param string $query
	 * @param string $endPoint
	 * @param number $httpcode
	 * @param string $response
	 * @access private
	 */
	private function errorLog($query,$data,$endPoint,$httpcode=0,$response=''){
		$error = 	"Error query  : " .$query."\n" .
					"Error endpoint: " .$endPoint."\n" .
					"Error http_response_code: " .$httpcode."\n" .
					"Error message: " .$response."\n";			
					"Error data: " .print_r($data,true)."\n";			
		if($this->_debug){
			echo '=========================>>>>>>'.$error ;
		}else{
			error_log($error);
		}
		return $error;
	}

	/**
	 * Print infos
	 * @param unknown_type $query
	 * @param unknown_type $endPoint
	 * @param unknown_type $httpcode
	 * @param unknown_type $response
	 * @access private
	 */
	private function debugLog($query,$endPoint,$httpcode='',$response=''){
		if($this->_debug)
		{
			$error = 	"\n#######################\n".
						"query				: " .$query."\n" .
                        "endpoint			: " .$endPoint."\n" .
                        "http_response_code	: " .$httpcode."\n" .
                        "message			: " .$response.
                        "\n#######################\n";

			echo $error ;
		}
	}
	
	/**
	 * Init an object Curl in function of proxy.
	 * @return an object of type Curl
	 * @access private
	 */
	private function initCurl(){
		$objCurl = new Curl();
		if($this->_proxy_host != null && $this->_proxy_port != null){
			$objCurl->set_proxy($this->_proxy_host.":".$this->_proxy_port);	
		}
		return $objCurl;
	}
}
